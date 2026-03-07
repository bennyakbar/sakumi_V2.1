<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SakumiDeploy extends Command
{
    protected $signature = 'sakumi:deploy';
    protected $description = 'Safe deploy procedure for SAKUMI';

    public function handle()
    {
        $this->info("Starting SAKUMI safe deploy...");

        exec('composer dump-autoload');
        exec('php artisan optimize:clear');

        $this->info("Cache cleared");
        $this->info("Autoload refreshed");

        $this->info("Restart PHP-FPM manually if needed.");

        return 0;
    }
}
