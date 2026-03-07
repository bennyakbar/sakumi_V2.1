<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SakumiCacheReset extends Command
{
    protected $signature = 'sakumi:cache-reset';
    protected $description = 'Reset all caches';

    public function handle()
    {
        $this->info("Resetting caches...");

        Artisan::call('permission:cache-reset');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $this->info("All caches cleared.");

        return Command::SUCCESS;
    }
}
