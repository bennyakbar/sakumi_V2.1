<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountMappingsSeeder extends Seeder
{
    public function run(): void
    {
        $units = DB::table('units')->select('id')->get();

        if ($units->isEmpty()) {
            return;
        }

        $templates = [
            // invoice.created => Dr Piutang / Cr Pendapatan
            ['event_type' => 'invoice.created', 'line_key' => 'receivable', 'entry_side' => 'debit', 'account_code' => '110100', 'priority' => 10, 'description' => 'Pengakuan piutang dari invoice'],
            ['event_type' => 'invoice.created', 'line_key' => 'revenue', 'entry_side' => 'credit', 'account_code' => '410100', 'priority' => 20, 'description' => 'Pengakuan pendapatan dari invoice'],

            // payment.posted => Dr Kas / Cr Piutang
            ['event_type' => 'payment.posted', 'line_key' => 'cash', 'entry_side' => 'debit', 'account_code' => '110200', 'priority' => 10, 'description' => 'Penerimaan kas pembayaran'],
            ['event_type' => 'payment.posted', 'line_key' => 'receivable', 'entry_side' => 'credit', 'account_code' => '110100', 'priority' => 20, 'description' => 'Pelunasan piutang pembayaran'],

            // payment.direct.posted => Dr Kas / Cr Pendapatan
            ['event_type' => 'payment.direct.posted', 'line_key' => 'cash', 'entry_side' => 'debit', 'account_code' => '110200', 'priority' => 10, 'description' => 'Penerimaan kas transaksi langsung'],
            ['event_type' => 'payment.direct.posted', 'line_key' => 'revenue', 'entry_side' => 'credit', 'account_code' => '410100', 'priority' => 20, 'description' => 'Pengakuan pendapatan transaksi langsung'],

            // settlement.applied => Dr Kas / Cr Piutang
            ['event_type' => 'settlement.applied', 'line_key' => 'cash', 'entry_side' => 'debit', 'account_code' => '110200', 'priority' => 10, 'description' => 'Penerimaan kas settlement'],
            ['event_type' => 'settlement.applied', 'line_key' => 'receivable', 'entry_side' => 'credit', 'account_code' => '110100', 'priority' => 20, 'description' => 'Pelunasan piutang settlement'],

            // expense.posted => Dr Beban / Cr Kas
            ['event_type' => 'expense.posted', 'line_key' => 'expense', 'entry_side' => 'debit', 'account_code' => '510100', 'priority' => 10, 'description' => 'Pengakuan beban transaksi pengeluaran'],
            ['event_type' => 'expense.posted', 'line_key' => 'cash', 'entry_side' => 'credit', 'account_code' => '110200', 'priority' => 20, 'description' => 'Pengeluaran kas transaksi pengeluaran'],
        ];

        $now = now();
        $rows = [];

        foreach ($units as $unit) {
            foreach ($templates as $template) {
                $rows[] = [
                    'unit_id' => $unit->id,
                    'event_type' => $template['event_type'],
                    'line_key' => $template['line_key'],
                    'entry_side' => $template['entry_side'],
                    'account_code' => $template['account_code'],
                    'priority' => $template['priority'],
                    'is_active' => true,
                    'description' => $template['description'],
                    'filters' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('account_mappings')->upsert(
            $rows,
            ['unit_id', 'event_type', 'line_key', 'entry_side', 'priority'],
            ['account_code', 'is_active', 'description', 'filters', 'updated_at']
        );
    }
}
