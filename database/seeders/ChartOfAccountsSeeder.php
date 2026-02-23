<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $units = DB::table('units')->select('id')->get();

        if ($units->isEmpty()) {
            return;
        }

        $templates = [
            [
                'code' => '110100',
                'name' => 'Piutang Siswa',
                'type' => 'asset',
                'normal_balance' => 'debit',
            ],
            [
                'code' => '110200',
                'name' => 'Kas dan Bank',
                'type' => 'asset',
                'normal_balance' => 'debit',
            ],
            [
                'code' => '410100',
                'name' => 'Pendapatan Pendidikan',
                'type' => 'revenue',
                'normal_balance' => 'credit',
            ],
        ];

        $now = now();
        $rows = [];

        foreach ($units as $unit) {
            foreach ($templates as $template) {
                $rows[] = [
                    'unit_id' => $unit->id,
                    'code' => $template['code'],
                    'name' => $template['name'],
                    'type' => $template['type'],
                    'normal_balance' => $template['normal_balance'],
                    'is_active' => true,
                    'parent_id' => null,
                    'meta' => json_encode(['seed' => 'accounting_engine_v2'], JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('chart_of_accounts')->upsert(
            $rows,
            ['unit_id', 'code'],
            ['name', 'type', 'normal_balance', 'is_active', 'meta', 'updated_at']
        );
    }
}
