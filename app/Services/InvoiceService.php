<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\StudentObligation;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateInvoiceNumber(): string
    {
        $year = now()->year;

        $last = Invoice::withoutGlobalScope('unit')
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('invoice_number');

        $sequence = 1;
        if ($last && preg_match('/(\d{6})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('INV-%s-%06d', $year, $sequence);
    }

    /**
     * Batch generate invoices for a given period.
     *
     * @return array{created: int, skipped: int, errors: array}
     */
    public function generateInvoices(
        string $periodType,
        string $periodIdentifier,
        int $userId,
        ?int $classId = null,
        ?int $categoryId = null,
        ?string $dueDate = null,
    ): array {
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        // Parse period to month/year for obligation lookup
        if ($periodType === 'monthly') {
            [$year, $month] = explode('-', $periodIdentifier);
            $month = (int) $month;
            $year = (int) $year;
        } elseif ($periodType === 'annual') {
            $year = (int) str_replace('AY', '', $periodIdentifier);
            $month = null;
        } else {
            $result['errors'][] = __('message.unsupported_period_type', ['type' => $periodType]);
            return $result;
        }

        // Find students with unpaid obligations for this period
        $studentQuery = Student::where('status', 'active');
        if ($classId) {
            $studentQuery->where('class_id', $classId);
        }
        if ($categoryId) {
            $studentQuery->where('category_id', $categoryId);
        }
        $students = $studentQuery->get();

        foreach ($students as $student) {
            try {
                $this->generateInvoiceForStudent(
                    $student, $periodType, $periodIdentifier, $month, $year, $userId, $dueDate, $result
                );
            } catch (\Throwable $e) {
                $result['errors'][] = "Student {$student->name} (ID:{$student->id}): {$e->getMessage()}";
            }
        }

        return $result;
    }

    private function generateInvoiceForStudent(
        Student $student,
        string $periodType,
        string $periodIdentifier,
        ?int $month,
        int $year,
        int $userId,
        ?string $dueDate,
        array &$result,
    ): void {
        // Find unpaid obligations for this student and period
        $obligationQuery = StudentObligation::where('student_id', $student->id)
            ->where('is_paid', false)
            ->where('year', $year);

        if ($month !== null) {
            $obligationQuery->where('month', $month);
        }

        // Exclude obligations already on a non-cancelled invoice
        $obligationQuery->whereDoesntHave('invoiceItems', function ($q) {
            $q->whereHas('invoice', fn ($iq) => $iq->where('status', '!=', 'cancelled'));
        });

        $obligations = $obligationQuery->with('feeType')->get();

        if ($obligations->isEmpty()) {
            $result['skipped']++;
            return;
        }

        DB::transaction(function () use ($student, $obligations, $periodType, $periodIdentifier, $userId, $dueDate, &$result) {
            $number = $this->generateInvoiceNumber();
            $totalAmount = $obligations->sum('amount');

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'student_id' => $student->id,
                'period_type' => $periodType,
                'period_identifier' => $periodIdentifier,
                'invoice_date' => now()->toDateString(),
                'due_date' => $dueDate ?? now()->addDays(30)->toDateString(),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'unpaid',
                'created_by' => $userId,
            ]);

            foreach ($obligations as $obligation) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'student_obligation_id' => $obligation->id,
                    'fee_type_id' => $obligation->fee_type_id,
                    'description' => $obligation->feeType->name,
                    'amount' => $obligation->amount,
                    'month' => $obligation->month,
                    'year' => $obligation->year,
                ]);
            }

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('invoice.created', ['unit_id' => $invoice->unit_id, 'source_type' => 'invoice', 'source_id' => $invoice->id, 'total_amount' => (float) $invoice->total_amount, 'effective_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(), 'created_by' => $userId, 'idempotency_key' => 'invoice.created:'.$invoice->id]); }

            $result['created']++;
        });
    }

    /**
     * Create a single invoice for specific obligations (manual creation).
     */
    public function createInvoice(int $studentId, array $obligationIds, array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($studentId, $obligationIds, $data, $userId) {
            $number = $this->generateInvoiceNumber();

            $obligations = StudentObligation::whereIn('id', $obligationIds)
                ->where('student_id', $studentId)
                ->where('is_paid', false)
                ->whereDoesntHave('invoiceItems', function ($q) {
                    $q->whereHas('invoice', fn ($iq) => $iq->where('status', '!=', 'cancelled'));
                })
                ->with('feeType')
                ->get();

            if ($obligations->isEmpty()) {
                throw new \RuntimeException(__('message.no_valid_obligations'));
            }

            if ($obligations->count() !== count($obligationIds)) {
                throw new \RuntimeException(__('message.obligations_already_invoiced'));
            }

            $totalAmount = $obligations->sum('amount');

            // Determine period from obligations
            $firstObligation = $obligations->first();
            $periodType = $data['period_type'] ?? 'monthly';
            $periodIdentifier = $data['period_identifier']
                ?? sprintf('%04d-%02d', $firstObligation->year, $firstObligation->month);

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'student_id' => $studentId,
                'period_type' => $periodType,
                'period_identifier' => $periodIdentifier,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'],
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($obligations as $obligation) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'student_obligation_id' => $obligation->id,
                    'fee_type_id' => $obligation->fee_type_id,
                    'description' => $obligation->feeType->name,
                    'amount' => $obligation->amount,
                    'month' => $obligation->month,
                    'year' => $obligation->year,
                ]);
            }

            if (config('features.accounting_engine_v2')) { AccountingEngine::fromEvent('invoice.created', ['unit_id' => $invoice->unit_id, 'source_type' => 'invoice', 'source_id' => $invoice->id, 'total_amount' => (float) $invoice->total_amount, 'effective_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(), 'created_by' => $userId, 'idempotency_key' => 'invoice.created:'.$invoice->id]); }

            return $invoice->load('items.feeType', 'student');
        });
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if ($invoice->status === 'paid') {
            throw new \RuntimeException(__('message.cannot_cancel_paid_invoice'));
        }

        if ((float) $invoice->paid_amount > 0) {
            throw new \RuntimeException(__('message.cannot_cancel_invoice_payments'));
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice->fresh();
    }
}
