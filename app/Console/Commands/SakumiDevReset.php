<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class SakumiDevReset extends Command
{
    protected $signature = 'sakumi:dev-reset';

    protected $description = 'Reset SAKUMI dummy database (SQLite)';

    public function handle()
    {
        $mode = config('database.sakumi_mode');

        if ($mode !== 'dummy') {
            $this->error('SAKUMI DEV RESET hanya boleh dijalankan pada mode DUMMY!');
            return Command::FAILURE;
        }

        $dbPath = database_path('sakumi_dummy.sqlite');

        $this->info('Resetting dummy database...');

        if (File::exists($dbPath)) {
            File::delete($dbPath);
        }

        File::put($dbPath, '');

        $this->info('Running migrations...');

        Artisan::call('migrate', [
            '--force' => true,
        ]);

        $this->info(Artisan::output());

        $this->info('Database dummy berhasil di-reset.');

        return Command::SUCCESS;
    }
}