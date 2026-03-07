<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Unit;

class SakumiSystemCheck extends Command
{
    protected $signature = 'sakumi:system-check';
    protected $description = 'Check overall system health';

    public function handle()
    {
        $this->info("SAKUMI SYSTEM CHECK");
        $this->line("-------------------");

        $this->info("APP_ENV: ".config('app.env'));

        try {
            DB::connection()->getPdo();
            $this->info("Database: OK");
        } catch (\Exception $e) {
            $this->error("Database: FAILED");
        }

        $this->info("Units: ".Unit::count());

        if (is_writable(storage_path())) {
            $this->info("Storage: writable");
        } else {
            $this->error("Storage: permission issue");
        }

        return Command::SUCCESS;
    }
}
