<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SakumiProductionAudit extends Command
{
    protected $signature = 'sakumi:production-audit';

    protected $description = 'Full production audit for SAKUMI system';

    public function handle()
    {
        $this->info('');
        $this->info('SAKUMI PRODUCTION AUDIT');
        $this->info('=======================');

        $this->checkDatabase();
        $this->checkTables();
        $this->runInternalChecks();
        $this->checkStorage();
        $this->checkDisk();

        $this->info('');
        $this->info('PRODUCTION AUDIT COMPLETED');
        $this->info('');
    }

    private function checkDatabase()
    {
        $this->info('');
        $this->info('DATABASE CHECK');

        try {

            DB::connection()->getPdo();

            $this->line('Database connection : OK');

        } catch (\Throwable $e) {

            $this->error('Database connection FAILED');
        }
    }

    private function checkTables()
    {
        $this->info('');
        $this->info('CORE TABLE CHECK');

        $tables = [
            'users',
            'roles',
            'permissions',
            'invoices',
            'transactions',
            'receipts',
            'settlements',
        ];

        foreach ($tables as $table) {

            try {

                DB::table($table)->count();

                $this->line("$table : OK");

            } catch (\Throwable $e) {

                $this->error("$table : MISSING");
            }
        }
    }

    private function runInternalChecks()
    {
        $this->info('');
        $this->info('RUNNING INTERNAL CHECKS');

        $commands = [
            'sakumi:rbac-check',
            'sakumi:finance-audit',
            'sakumi:ledger-check',
            'sakumi:unit-check',
            'sakumi:system-check'
        ];

        foreach ($commands as $command) {

            $this->line('');
            $this->line("Running $command");

            try {

                Artisan::call($command);

                $this->line(Artisan::output());

            } catch (\Throwable $e) {

                $this->error("FAILED: $command");

            }
        }
    }

    private function checkStorage()
    {
        $this->info('');
        $this->info('STORAGE CHECK');

        $path = storage_path();

        if (is_writable($path)) {

            $this->line('Storage writable : OK');

        } else {

            $this->error('Storage NOT writable');
        }
    }

    private function checkDisk()
    {
        $this->info('');
        $this->info('DISK SPACE CHECK');

        $free = disk_free_space('/');
        $total = disk_total_space('/');

        $percent = ($free / $total) * 100;

        $this->line('Free disk space : ' . round($percent,2) . '%');

        if ($percent < 10) {

            $this->warn('Disk space LOW');

        }
    }
}
