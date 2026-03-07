<?php

namespace App\Services;

use App\Models\PromotionBatch;
use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PromotionService
{
    public function applyBatch(int $batchId, int $actorUserId): void
    {
        DB::transaction(function () use ($batchId, $actorUserId): void {
            $batch = PromotionBatch::query()
                ->with(['items.student', 'items.fromEnrollment'])
                ->lockForUpdate()
                ->findOrFail($batchId);

            if ($batch->status !== 'approved') {
                throw new RuntimeException('Promotion batch must be approved before applying.');
            }

            foreach ($batch->items as $item) {
                if ($item->is_applied) {
                    continue;
                }

                $fromEnrollment = StudentEnrollment::query()
                    ->lockForUpdate()
                    ->findOrFail($item->from_enrollment_id);

                if ((int) $fromEnrollment->student_id !== (int) $item->student_id) {
                    throw new RuntimeException("Enrollment {$fromEnrollment->id} is not owned by student {$item->student_id}.");
                }

                if ((int) $fromEnrollment->academic_year_id !== (int) $batch->from_academic_year_id) {
                    throw new RuntimeException("Enrollment {$fromEnrollment->id} is outside source academic year.");
                }

                if (! $fromEnrollment->is_current) {
                    throw new RuntimeException("Enrollment {$fromEnrollment->id} is not current.");
                }

                $fromEnrollment->update([
                    'is_current' => false,
                    'end_date' => $batch->effective_date->copy()->subDay(),
                    'exit_status' => match ($item->action) {
                        'promote' => 'promoted',
                        'retain' => 'retained',
                        'graduate' => 'graduated',
                        default => throw new RuntimeException("Invalid action {$item->action}."),
                    },
                    'promotion_batch_id' => $batch->id,
                ]);

                if (in_array($item->action, ['promote', 'retain'], true)) {
                    if (! $item->to_class_id) {
                        throw new RuntimeException("Action {$item->action} requires to_class_id.");
                    }

                    $newEnrollment = StudentEnrollment::query()->create([
                        'unit_id' => $batch->unit_id,
                        'student_id' => $item->student_id,
                        'academic_year_id' => $batch->to_academic_year_id,
                        'class_id' => $item->to_class_id,
                        'start_date' => $batch->effective_date,
                        'end_date' => null,
                        'is_current' => true,
                        'entry_status' => $item->action === 'promote' ? 'promoted' : 'retained',
                        'promotion_batch_id' => $batch->id,
                        'previous_enrollment_id' => $fromEnrollment->id,
                    ]);

                    // Keep existing flows working while enrollment-aware queries are rolled out.
                    $item->student->update([
                        'class_id' => $newEnrollment->class_id,
                        'status' => 'active',
                    ]);
                } elseif ($item->action === 'graduate') {
                    $item->student->update(['status' => 'graduated']);
                }

                $item->update([
                    'is_applied' => true,
                    'applied_at' => now(),
                ]);
            }

            $batch->update([
                'status' => 'applied',
                'applied_by' => $actorUserId,
            ]);
        });
    }
}
