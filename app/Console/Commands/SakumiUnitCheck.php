<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Unit;

class SakumiUnitCheck extends Command
{
    protected $signature = 'sakumi:unit-check';
    protected $description = 'Check unit assignments';

    public function handle()
    {
        $this->info("UNIT CHECK");

        $this->line("----------------");

        $this->info("Units: ".Unit::count());

        $orphans = User::whereNull('unit_id')->count();

        if ($orphans > 0) {
            $this->error("Users without unit: $orphans");
        } else {
            $this->info("No orphan users");
        }

        return Command::SUCCESS;
    }
}
