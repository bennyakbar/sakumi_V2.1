<?php

namespace App\Services;

use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermanentDeleteService
{
    public const CONFIRMATION_PHRASE = 'HAPUS PERMANEN';
    public const ENTITY_USER = 'user';
    public const ENTITY_STUDENT = 'student';
    public const ENTITY_CLASS = 'class';
    public const ENTITY_CATEGORY = 'category';
    public const ENTITY_FEE_TYPE = 'fee_type';
    public const ENTITY_FEE_MATRIX = 'fee_matrix';

    /**
     * @return array<int, string>
     */
    public function supportedEntities(): array
    {
        return [
            self::ENTITY_USER,
            self::ENTITY_STUDENT,
            self::ENTITY_CLASS,
            self::ENTITY_CATEGORY,
            self::ENTITY_FEE_TYPE,
            self::ENTITY_FEE_MATRIX,
        ];
    }

    public function isRequested(Request $request): bool
    {
        return $request->boolean('permanent_delete');
    }

    public function isFeatureEnabled(): bool
    {
        return filter_var(Setting::get('dangerous_permanent_delete_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function isAllowedFor(User $actor): bool
    {
        return $actor->hasRole('super_admin') && $this->isFeatureEnabled();
    }

    public function hasValidConfirmation(Request $request): bool
    {
        return trim((string) $request->input('confirm_text', '')) === self::CONFIRMATION_PHRASE;
    }

    /**
     * @return array<string, int>
     */
    public function dependencyCounts(string $entity, int $id): array
    {
        return match ($entity) {
            self::ENTITY_USER => [
                'transactions.created_by' => (int) DB::table('transactions')->where('created_by', $id)->count(),
                'transactions.cancelled_by' => (int) DB::table('transactions')->where('cancelled_by', $id)->count(),
                'invoices.created_by' => (int) DB::table('invoices')->where('created_by', $id)->count(),
                'settlements.created_by' => (int) DB::table('settlements')->where('created_by', $id)->count(),
            ],
            self::ENTITY_STUDENT => [
                'transactions' => (int) DB::table('transactions')->where('student_id', $id)->count(),
                'invoices' => (int) DB::table('invoices')->where('student_id', $id)->count(),
                'settlements' => (int) DB::table('settlements')->where('student_id', $id)->count(),
                'student_obligations' => (int) DB::table('student_obligations')->where('student_id', $id)->count(),
                'notifications' => (int) DB::table('notifications')->where('student_id', $id)->count(),
                'student_fee_mappings' => (int) DB::table('student_fee_mappings')->where('student_id', $id)->count(),
            ],
            self::ENTITY_CLASS => [
                'students' => (int) DB::table('students')->where('class_id', $id)->count(),
                'fee_matrix' => (int) DB::table('fee_matrix')->where('class_id', $id)->count(),
            ],
            self::ENTITY_CATEGORY => [
                'students' => (int) DB::table('students')->where('category_id', $id)->count(),
                'fee_matrix' => (int) DB::table('fee_matrix')->where('category_id', $id)->count(),
            ],
            self::ENTITY_FEE_TYPE => [
                'fee_matrix' => (int) DB::table('fee_matrix')->where('fee_type_id', $id)->count(),
                'transaction_items' => (int) DB::table('transaction_items')->where('fee_type_id', $id)->count(),
                'student_obligations' => (int) DB::table('student_obligations')->where('fee_type_id', $id)->count(),
                'invoice_items' => (int) DB::table('invoice_items')->where('fee_type_id', $id)->count(),
            ],
            self::ENTITY_FEE_MATRIX => [
                'student_fee_mappings' => (int) DB::table('student_fee_mappings')->where('fee_matrix_id', $id)->count(),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, int>  $counts
     * @return array<string, int>
     */
    public function onlyBlockingDependencies(array $counts): array
    {
        return array_filter($counts, fn ($count) => (int) $count > 0);
    }

    /**
     * @param  array<string, int>  $counts
     */
    public function formatDependencies(array $counts): string
    {
        $parts = [];
        foreach ($counts as $key => $count) {
            $parts[] = "{$key}:{$count}";
        }

        return implode(', ', $parts);
    }

    public function resolveEntityModel(string $entity, int $id): ?Model
    {
        return match ($entity) {
            self::ENTITY_USER => User::query()->find($id),
            self::ENTITY_STUDENT => Student::query()->find($id),
            self::ENTITY_CLASS => SchoolClass::query()->find($id),
            self::ENTITY_CATEGORY => StudentCategory::query()->find($id),
            self::ENTITY_FEE_TYPE => FeeType::query()->find($id),
            self::ENTITY_FEE_MATRIX => FeeMatrix::query()->find($id),
            default => null,
        };
    }

    /**
     * @param  array<string, int>  $dependencies
     */
    public function logSnapshot(User $actor, string $entity, Model $model, array $dependencies, string $status, ?string $error = null): void
    {
        $snapshot = $model->getAttributes();
        if ($model instanceof User) {
            $snapshot['roles'] = $model->roles()->pluck('name')->values()->all();
        }

        activity('permanent-delete')
            ->causedBy($actor)
            ->withProperties([
                'entity' => $entity,
                'entity_id' => $model->getKey(),
                'model_class' => get_class($model),
                'dependencies' => $dependencies,
                'snapshot' => $snapshot,
                'error' => $error,
            ])
            ->log("permanent-delete.{$entity}.{$status}");
    }
}
