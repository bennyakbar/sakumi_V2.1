<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $canonicalUnits = [
            ['code' => 'MI', 'name' => 'Madrasah Ibtidaiyah (MI)'],
            ['code' => 'RA', 'name' => 'Raudhatul Athfal (RA)'],
            ['code' => 'DTA', 'name' => 'Diniyah Takmiliyah Awaliyah (DTA)'],
        ];

        DB::transaction(function () use ($canonicalUnits): void {
            foreach ($canonicalUnits as $unit) {
                Unit::query()->updateOrCreate(
                    ['code' => $unit['code']],
                    ['name' => $unit['name'], 'is_active' => true]
                );
            }

            $allowedCodes = collect($canonicalUnits)->pluck('code')->all();
            $allowedIds = Unit::query()
                ->whereIn('code', $allowedCodes)
                ->pluck('id')
                ->all();

            $miId = Unit::query()->where('code', 'MI')->value('id');
            if ($miId === null) {
                return;
            }

            $scopedTables = [
                'users',
                'classes',
                'student_categories',
                'fee_types',
                'fee_matrix',
                'students',
                'accounts',
                'categories',
                'transactions',
                'student_obligations',
                'invoices',
                'settlements',
                'notifications',
                'expense_fee_categories',
                'expense_fee_subcategories',
            ];

            foreach ($scopedTables as $table) {
                if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, 'unit_id')) {
                    continue;
                }

                DB::table($table)
                    ->whereNotIn('unit_id', $allowedIds)
                    ->update(['unit_id' => $miId]);
            }

            Unit::query()
                ->whereNotIn('code', $allowedCodes)
                ->delete();
        });
    }
}
