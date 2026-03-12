<?php

namespace App\Services;

use App\Models\DocumentSequence;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Settlement;
use App\Models\Student;
use App\Models\StudentObligation;
use App\Models\Unit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private readonly ArrearsService $arrearsService,
        private readonly SettlementService $settlementService,
    ) {
    }

    public function generateInvoiceNumber(int $unitId): string
    {
        $unitCode = Unit::withoutGlobalScope('unit')
            ->whereKey($unitId)
            ->value('code') ?? "U{$unitId}";
        $unitCode = strtoupper((string) $unitCode);
        $year = now()->year;
        $seqPrefix = "INV-{$unitCode}-{$year}";

        $sequence = DocumentSequence::next($seqPrefix);

        return sprintf('INV-%s-%s-%06d', $unitCode, $year, $sequence);
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
            // Ensure obligations are up to date, including student-level fee mappings.
            $this->arrearsService->generateMonthlyObligations($month, $year);
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
            $number = $this->generateInvoiceNumber((int) $student->unit_id);
            $totalAmount = $obligations->sum('amount');
            $firstObligation = $obligations->first();

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'student_id' => $student->id,
                'academic_year_id' => $firstObligation?->academic_year_id,
                'student_enrollment_id' => $firstObligation?->student_enrollment_id,
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
                    'description' => $obligation->feeType?->name ?? __('message.unknown_fee_type'),
                    'amount' => $obligation->amount,
                    'month' => $obligation->month,
                    'year' => $obligation->year,
                ]);
            }

            AccountingEngine::fromEvent('invoice.created', [
                'unit_id' => $invoice->unit_id,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'total_amount' => (float) $invoice->total_amount,
                'effective_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'created_by' => $userId,
                'idempotency_key' => 'invoice.created:'.$invoice->id,
            ]);

            $result['created']++;
        });
    }

    /**
     * Create a single invoice for specific obligations (manual creation).
     */
    public function createInvoice(int $studentId, array $obligationIds, array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($studentId, $obligationIds, $data, $userId) {
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

            $number = $this->generateInvoiceNumber((int) $obligations->first()->unit_id);
            $totalAmount = $obligations->sum('amount');

            // Determine period from obligations
            $firstObligation = $obligations->first();
            $periodType = $data['period_type'] ?? 'monthly';
            $periodIdentifier = $data['period_identifier']
                ?? sprintf('%04d-%02d', $firstObligation->year, $firstObligation->month);

            $academicYearId = $obligations->pluck('academic_year_id')->filter()->unique();
            $enrollmentId = $obligations->pluck('student_enrollment_id')->filter()->unique();

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId->count() === 1 ? $academicYearId->first() : null,
                'student_enrollment_id' => $enrollmentId->count() === 1 ? $enrollmentId->first() : null,
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
                    'description' => $obligation->feeType?->name ?? __('message.unknown_fee_type'),
                    'amount' => $obligation->amount,
                    'month' => $obligation->month,
                    'year' => $obligation->year,
                ]);
            }

            AccountingEngine::fromEvent('invoice.created', [
                'unit_id' => $invoice->unit_id,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'total_amount' => (float) $invoice->total_amount,
                'effective_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'created_by' => $userId,
                'idempotency_key' => 'invoice.created:'.$invoice->id,
            ]);

            return $invoice->load('items.feeType', 'student');
        });
    }

    public function cancel(Invoice $invoice, ?int $userId = null, ?string $reason = null): Invoice
    {
        if ($invoice->status === 'paid') {
            if (!$userId) {
                throw new \RuntimeException(__('message.cannot_cancel_paid_invoice'));
            }

            return $this->voidWithPayments($invoice, $userId, $reason);
        }

        if ((float) $invoice->paid_amount > 0) {
            if (!$userId) {
                throw new \RuntimeException(__('message.cannot_cancel_invoice_payments'));
            }

            return $this->voidWithPayments($invoice, $userId, $reason);
        }

        $invoice->update(['status' => 'cancelled']);

        return $invoice->fresh();
    }

    private function voidWithPayments(Invoice $invoice, int $userId, ?string $reason = null): Invoice
    {
        // Ensure the acting user has settlement void permission before cascading.
        // Require authenticated user — prevent bypass in CLI/queue context.
        $user = Auth::user();
        if (! $user) {
            throw new \RuntimeException('Authenticated user is required to void settlements from invoice cancellation.');
        }
        if (! $user->can('settlements.void')) {
            throw new \RuntimeException(__('message.cancel_paid_invoice_requires_void_permission'));
        }

        $reason = trim((string) ($reason ?: 'Invoice void with payment correction'));

        return DB::transaction(function () use ($invoice, $userId, $reason) {
            $settlements = Settlement::query()
                ->where('status', 'completed')
                ->whereHas('allocations', fn ($q) => $q->where('invoice_id', $invoice->id))
                ->withCount('allocations')
                ->get();

            foreach ($settlements as $settlement) {
                // Void the entire settlement — SettlementService::void() properly
                // recalculates all affected invoices, not just the target one.
                $this->settlementService->void(
                    settlement: $settlement,
                    userId: $userId,
                    reason: $reason . ((int) $settlement->allocations_count > 1
                        ? " (cascaded from invoice {$invoice->invoice_number}; settlement {$settlement->settlement_number} had {$settlement->allocations_count} allocations)"
                        : ''),
                );
            }

            $invoice->refresh();
            $invoice->recalculateFromAllocations();
            $invoice->update([
                'status' => 'cancelled',
                'notes' => trim((string) ($invoice->notes ? $invoice->notes . "\n" : '') . 'VOID: ' . $reason),
            ]);

            return $invoice->fresh();
        });
    }
}
