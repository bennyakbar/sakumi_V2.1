<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\AdmissionPeriodQuota;
use App\Models\FeeMatrix;
use App\Models\Student;
use App\Models\StudentFeeMapping;
use App\Models\StudentObligation;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function generateRegistrationNumber(int $unitId): string
    {
        $unitCode = Unit::withoutGlobalScope('unit')
            ->whereKey($unitId)
            ->value('code') ?? "U{$unitId}";
        $unitCode = strtoupper((string) $unitCode);
        $year = now()->year;

        $last = Applicant::withoutGlobalScope('unit')
            ->where('unit_id', $unitId)
            ->where('registration_number', 'like', "REG-{$unitCode}-{$year}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('registration_number');

        $sequence = 1;
        if ($last && preg_match('/(\d{4})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('REG-%s-%s-%04d', $unitCode, $year, $sequence);
    }

    public function moveToReview(Applicant $applicant, int $userId): Applicant
    {
        if ($applicant->status !== 'registered') {
            throw new \RuntimeException(__('message.admission_invalid_transition'));
        }

        $applicant->update([
            'status' => 'under_review',
            'status_changed_at' => now()->toDateString(),
            'status_changed_by' => $userId,
        ]);

        return $applicant->fresh();
    }

    public function accept(Applicant $applicant, int $userId): Applicant
    {
        if ($applicant->status !== 'under_review') {
            throw new \RuntimeException(__('message.admission_invalid_transition'));
        }

        // Check quota
        $quota = AdmissionPeriodQuota::where('admission_period_id', $applicant->admission_period_id)
            ->where('class_id', $applicant->target_class_id)
            ->first();

        if ($quota) {
            $acceptedCount = Applicant::where('admission_period_id', $applicant->admission_period_id)
                ->where('target_class_id', $applicant->target_class_id)
                ->whereIn('status', ['accepted', 'enrolled'])
                ->count();

            if ($acceptedCount >= $quota->quota) {
                throw new \RuntimeException(__('message.admission_quota_exceeded'));
            }
        }

        $applicant->update([
            'status' => 'accepted',
            'status_changed_at' => now()->toDateString(),
            'status_changed_by' => $userId,
        ]);

        return $applicant->fresh();
    }

    public function reject(Applicant $applicant, int $userId, ?string $reason = null): Applicant
    {
        if (! in_array($applicant->status, ['registered', 'under_review'], true)) {
            throw new \RuntimeException(__('message.admission_invalid_transition'));
        }

        $applicant->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'status_changed_at' => now()->toDateString(),
            'status_changed_by' => $userId,
        ]);

        return $applicant->fresh();
    }

    public function enroll(Applicant $applicant, int $userId, ?string $nis = null): Applicant
    {
        if ($applicant->status !== 'accepted') {
            throw new \RuntimeException(__('message.admission_invalid_transition'));
        }

        return DB::transaction(function () use ($applicant, $userId, $nis) {
            $unitId = (int) $applicant->unit_id;

            // 1. Create student
            $student = Student::create([
                'unit_id' => $unitId,
                'nis' => $nis ?? $this->generateNis($unitId),
                'name' => $applicant->name,
                'class_id' => $applicant->target_class_id,
                'category_id' => $applicant->category_id,
                'gender' => $applicant->gender,
                'birth_date' => $applicant->birth_date,
                'birth_place' => $applicant->birth_place,
                'parent_name' => $applicant->parent_name,
                'parent_phone' => $applicant->parent_phone,
                'parent_whatsapp' => $applicant->parent_whatsapp,
                'address' => $applicant->address,
                'status' => 'active',
                'enrollment_date' => now()->toDateString(),
            ]);

            // 2. Monthly fee mappings from FeeMatrix
            $monthlyMatrices = FeeMatrix::withoutGlobalScope('unit')
                ->where('unit_id', $unitId)
                ->where('is_active', true)
                ->where(function ($q) use ($applicant) {
                    $q->where('class_id', $applicant->target_class_id)->orWhereNull('class_id');
                })
                ->where(function ($q) use ($applicant) {
                    $q->where('category_id', $applicant->category_id)->orWhereNull('category_id');
                })
                ->whereHas('feeType', fn ($q) => $q->where('is_monthly', true))
                ->get();

            foreach ($monthlyMatrices as $matrix) {
                StudentFeeMapping::create([
                    'unit_id' => $unitId,
                    'student_id' => $student->id,
                    'fee_matrix_id' => $matrix->id,
                    'effective_from' => now()->toDateString(),
                    'is_active' => true,
                    'created_by' => $userId,
                ]);
            }

            // 3. One-time/registration fee obligations from FeeMatrix
            $oneTimeMatrices = FeeMatrix::withoutGlobalScope('unit')
                ->where('unit_id', $unitId)
                ->where('is_active', true)
                ->where(function ($q) use ($applicant) {
                    $q->where('class_id', $applicant->target_class_id)->orWhereNull('class_id');
                })
                ->where(function ($q) use ($applicant) {
                    $q->where('category_id', $applicant->category_id)->orWhereNull('category_id');
                })
                ->whereHas('feeType', fn ($q) => $q->where('is_monthly', false))
                ->with('feeType')
                ->get();

            $obligationIds = [];
            $year = now()->year;

            foreach ($oneTimeMatrices as $matrix) {
                $obligation = StudentObligation::create([
                    'unit_id' => $unitId,
                    'student_id' => $student->id,
                    'fee_type_id' => $matrix->fee_type_id,
                    'month' => now()->month,
                    'year' => $year,
                    'amount' => $matrix->amount,
                    'is_paid' => false,
                    'paid_amount' => 0,
                ]);
                $obligationIds[] = $obligation->id;
            }

            // 4. Create registration invoice if there are obligations
            if (! empty($obligationIds)) {
                $academicYear = $applicant->admissionPeriod->academic_year ?? $year;
                $this->invoiceService->createInvoice(
                    $student->id,
                    $obligationIds,
                    [
                        'period_type' => 'registration',
                        'period_identifier' => "REG-{$academicYear}",
                        'due_date' => now()->addDays(30)->toDateString(),
                        'notes' => "Registration invoice for {$applicant->registration_number}",
                    ],
                    $userId,
                );
            }

            // 5. Update applicant status
            $applicant->update([
                'status' => 'enrolled',
                'student_id' => $student->id,
                'status_changed_at' => now()->toDateString(),
                'status_changed_by' => $userId,
            ]);

            return $applicant->fresh();
        });
    }

    public function bulkUpdateStatus(array $ids, string $status, int $userId, ?string $reason = null): int
    {
        $updated = 0;
        $applicants = Applicant::whereIn('id', $ids)->get();

        foreach ($applicants as $applicant) {
            try {
                match ($status) {
                    'under_review' => $this->moveToReview($applicant, $userId),
                    'accepted' => $this->accept($applicant, $userId),
                    'rejected' => $this->reject($applicant, $userId, $reason),
                    default => throw new \RuntimeException(__('message.admission_invalid_status')),
                };
                $updated++;
            } catch (\Throwable) {
                // Skip individual failures in bulk operation
            }
        }

        return $updated;
    }

    private function generateNis(int $unitId): string
    {
        $unitCode = Unit::withoutGlobalScope('unit')
            ->whereKey($unitId)
            ->value('code') ?? "U{$unitId}";
        $unitCode = strtoupper((string) $unitCode);
        $year = now()->year;

        $last = Student::withoutGlobalScope('unit')
            ->where('unit_id', $unitId)
            ->where('nis', 'like', "{$year}{$unitCode}%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('nis');

        $sequence = 1;
        if ($last && preg_match('/(\d{4})$/', $last, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return sprintf('%s%s%04d', $year, $unitCode, $sequence);
    }
}
