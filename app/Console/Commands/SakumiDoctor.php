<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SakumiDoctor extends Command
{
    protected $signature = 'sakumi:doctor';

    protected $description = 'Run full SAKUMI system diagnostics';

    public function handle()
    {
        $this->info('');
        $this->info('SAKUMI SYSTEM DOCTOR');
        $this->info('====================');
        $this->info('');

        $checks = [
            'RBAC CHECK'   => 'sakumi:rbac-check',
            'FINANCE AUDIT'=> 'sakumi:finance-audit',
            'LEDGER CHECK' => 'sakumi:ledger-check',
            'UNIT CHECK'   => 'sakumi:unit-check',
            'SYSTEM CHECK' => 'sakumi:system-check',
        ];

        foreach ($checks as $title => $command) {

            $this->info('');
            $this->info("Running: $title");
            $this->info(str_repeat('-', 30));

            try {

                Artisan::call($command);

                $this->line(Artisan::output());

            } catch (\Throwable $e) {

                $this->error("ERROR running $command");
                $this->error($e->getMessage());

            }
        }

        $this->info('');
        $this->info('SAKUMI DOCTOR COMPLETED');
        $this->info('');
    }
}
