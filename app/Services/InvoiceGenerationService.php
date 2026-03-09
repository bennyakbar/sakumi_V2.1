<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\Student;
use App\Models\StudentObligation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceGenerationService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly ArrearsService $arrearsService,
    ) {}

    /**
     * Generate monthly invoices from active templates for a given month.
     *
     * This method is idempotent — running it multiple times for the same month
     * will not create duplicate invoices.
     *
     * @return array{created: int, skipped: int, errors: array<string>}
     */
    public function generateMonthlyInvoices(int $month, int $year, int $userId, ?int $classId = null): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        $templates = InvoiceTemplate::where('is_active', true)
            ->where('billing_cycle', 'monthly')
            ->with('feeType')
            ->get();

        if ($templates->isEmpty()) {
            $result['errors'][] = 'No active monthly invoice templates found.';
            return $result;
        }

        // Ensure obligations exist for this month before generating invoices
        $this->arrearsService->generateMonthlyObligations($month, $year);

        $periodIdentifier = sprintf('%04d-%02d', $year, $month);

        // Fetch active students
        $studentQuery = Student::where('status', 'active');
        if ($classId) {
            $studentQuery->where('class_id', $classId);
        }
        $students = $studentQuery->get();

        foreach ($students as $student) {
            foreach ($templates as $template) {
                try {
                    $generated = $this->generateForStudentTemplate(
                        $student, $template, $month, $year, $periodIdentifier, $userId
                    );

                    if ($generated) {
                        $result['created']++;
                    } else {
                        $result['skipped']++;
                    }
                } catch (\Throwable $e) {
                    $msg = "Student {$student->name} (ID:{$student->id}), Template {$template->name}: {$e->getMessage()}";
                    $result['errors'][] = $msg;
                    Log::error('Monthly invoice generation error', ['message' => $msg]);
                }
            }
        }

        return $result;
    }

    /**
     * Generate a single invoice for a student from a template.
     *
     * Returns true if an invoice was created, false if skipped (duplicate).
     */
    private function generateForStudentTemplate(
        Student $student,
        InvoiceTemplate $template,
        int $month,
        int $year,
        string $periodIdentifier,
        int $userId,
    ): bool {
        // Duplicate check: does an active invoice already exist for this student,
        // fee type, and billing month?
        $exists = Invoice::where('student_id', $student->id)
            ->where('period_type', 'monthly')
            ->where('period_identifier', $periodIdentifier)
            ->where('status', '!=', 'cancelled')
            ->whereHas('items', fn ($q) => $q->where('fee_type_id', $template->fee_type_id))
            ->exists();

        if ($exists) {
            return false;
        }

        // Find the matching unpaid obligation (if any)
        $obligation = StudentObligation::where('student_id', $student->id)
            ->where('fee_type_id', $template->fee_type_id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_paid', false)
            ->whereDoesntHave('invoiceItems', function ($q) {
                $q->whereHas('invoice', fn ($iq) => $iq->where('status', '!=', 'cancelled'));
            })
            ->first();

        if (! $obligation) {
            return false;
        }

        DB::transaction(function () use ($student, $template, $obligation, $periodIdentifier, $userId) {
            $number = $this->invoiceService->generateInvoiceNumber((int) $student->unit_id);

            $invoice = Invoice::create([
                'invoice_number' => $number,
                'student_id' => $student->id,
                'academic_year_id' => $obligation->academic_year_id,
                'student_enrollment_id' => $obligation->student_enrollment_id,
                'period_type' => 'monthly',
                'period_identifier' => $periodIdentifier,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'total_amount' => $obligation->amount,
                'paid_amount' => 0,
                'status' => 'unpaid',
                'notes' => "Auto-generated from template: {$template->name}",
                'created_by' => $userId,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'student_obligation_id' => $obligation->id,
                'fee_type_id' => $template->fee_type_id,
                'description' => $template->name,
                'amount' => $obligation->amount,
                'month' => $obligation->month,
                'year' => $obligation->year,
            ]);

            AccountingEngine::fromEvent('invoice.created', [
                'unit_id' => $invoice->unit_id,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'total_amount' => (float) $invoice->total_amount,
                'effective_date' => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'created_by' => $userId,
                'idempotency_key' => 'invoice.created:' . $invoice->id,
            ]);
        });

        return true;
    }
}
