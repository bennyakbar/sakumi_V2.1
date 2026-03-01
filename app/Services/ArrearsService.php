<?php

namespace App\Services;

use App\Models\FeeMatrix;
use App\Models\Student;
use App\Models\StudentFeeMapping;
use App\Models\StudentEnrollment;
use App\Models\StudentObligation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ArrearsService
{
    public function generateMonthlyObligations(int $month, int $year): int
    {
        return DB::transaction(function () use ($month, $year) {
            $students = Student::where('status', 'active')->get();
            $created = 0;
            $periodDate = Carbon::create($year, $month, 1)->startOfDay();

            foreach ($students as $student) {
                $enrollment = $this->resolveEnrollmentAtDate($student, $periodDate);
                $classId = $enrollment?->class_id ?? $student->class_id;
                $academicYearId = $enrollment?->academic_year_id;
                $feeEntries = $this->resolveFeeEntriesForStudentAtDate($student, $periodDate, (int) $classId);

                foreach ($feeEntries as $entry) {
                    $existing = StudentObligation::query()
                        ->where('student_id', $student->id)
                        ->where('fee_type_id', $entry->fee_type_id)
                        ->where('month', $month)
                        ->where('year', $year)
                        ->first();

                    if (! $existing) {
                        StudentObligation::query()->create([
                            'unit_id' => $student->unit_id,
                            'academic_year_id' => $academicYearId,
                            'student_id' => $student->id,
                            'student_enrollment_id' => $enrollment?->id,
                            'class_id_snapshot' => $classId,
                            'fee_type_id' => $entry->fee_type_id,
                            'month' => $month,
                            'year' => $year,
                            'amount' => $entry->amount,
                            'is_paid' => false,
                            'paid_amount' => 0,
                        ]);
                        $created++;
                        continue;
                    }

                    // Keep obligations idempotent but allow tariff correction only before
                    // the obligation has EVER been referenced by any invoice (including cancelled).
                    // Once invoiced, the amount is frozen to preserve audit trail integrity.
                    $hasAnyInvoice = $existing->invoiceItems()->exists();

                    if (! $existing->is_paid && ! $hasAnyInvoice && $existing->transaction_item_id === null) {
                        $newAmount = (float) $entry->amount;
                        if ((float) $existing->amount !== $newAmount) {
                            $existing->update([
                                'amount' => $newAmount,
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            return $created;
        });
    }

    /**
     * Resolve applicable fee matrix rows for a student at a specific period date.
     * Priority: explicit student mapping -> fallback class/category matrix.
     *
     * @return \Illuminate\Support\Collection<int, FeeMatrix>
     */
    private function resolveFeeEntriesForStudentAtDate(Student $student, Carbon $periodDate, int $classId)
    {
        $mappingEntries = StudentFeeMapping::query()
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $periodDate->toDateString())
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $periodDate->toDateString());
            })
            ->with(['feeMatrix' => function ($q) use ($periodDate) {
                $q->where('is_active', true)
                    ->whereDate('effective_from', '<=', $periodDate->toDateString())
                    ->where(function ($iq) use ($periodDate) {
                        $iq->whereNull('effective_to')
                            ->orWhereDate('effective_to', '>=', $periodDate->toDateString());
                    })
                    ->whereHas('feeType', fn ($fq) => $fq->where('is_monthly', true)->where('is_active', true))
                    ->with('feeType');
            }])
            ->orderByDesc('effective_from')
            ->get()
            ->pluck('feeMatrix')
            ->filter()
            ->unique('fee_type_id')
            ->values();

        if ($mappingEntries->isNotEmpty()) {
            return $mappingEntries;
        }

        return FeeMatrix::query()
            ->where('is_active', true)
            ->whereHas('feeType', fn ($q) => $q->where('is_monthly', true)->where('is_active', true))
            ->where(function ($q) use ($classId) {
                $q->whereNull('class_id')->orWhere('class_id', $classId);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('category_id')->orWhere('category_id', $student->category_id);
            })
            ->whereDate('effective_from', '<=', $periodDate->toDateString())
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $periodDate->toDateString());
            })
            ->orderByRaw('class_id DESC NULLS LAST, category_id DESC NULLS LAST')
            ->get()
            ->unique('fee_type_id')
            ->values();
    }

    private function resolveEnrollmentAtDate(Student $student, Carbon $periodDate): ?StudentEnrollment
    {
        return StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->whereDate('start_date', '<=', $periodDate->toDateString())
            ->where(function ($q) use ($periodDate) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $periodDate->toDateString());
            })
            ->orderByDesc('start_date')
            ->first();
    }

    public function getArrearsByStudent(int $studentId): array
    {
        return StudentObligation::where('student_id', $studentId)
            ->where('is_paid', false)
            ->with('feeType')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    public function getArrearsSummaryByClass(int $classId): array
    {
        return StudentObligation::where('is_paid', false)
            ->whereHas('student', fn ($q) => $q->where('class_id', $classId)->where('status', 'active'))
            ->with('student', 'feeType')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->groupBy('student_id')
            ->toArray();
    }
}
