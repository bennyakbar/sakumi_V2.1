<?php

namespace App\Console\Commands;

use Database\Seeders\Testing\DummyDatabaseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class InitDummyDatabase extends Command
{
    protected $signature = 'app:init-dummy';

    protected $description = 'Initialize dummy database (migrate missing tables + seed testing data)';

    public function handle(): int
    {
        if (! app()->environment('testing')) {
            $this->error('Safety check failed: APP_ENV must be "testing".');

            return self::FAILURE;
        }

        $sakumiMode = env('DB_SAKUMI_MODE');
        if ($sakumiMode !== 'dummy') {
            $this->error('Safety check failed: DB_SAKUMI_MODE must be "dummy".');
            $this->error('Current value: '.var_export($sakumiMode, true));

            return self::FAILURE;
        }

        // Ensure SQLite file exists
        $dbPath = database_path('sakumi_dummy.sqlite');
        if (! file_exists($dbPath)) {
            touch($dbPath);
            $this->info('Created SQLite file: '.$dbPath);
        }

        $requiredTables = [
            'units',
            'users',
            'classes',
            'student_categories',
            'students',
            'accounts',
            'categories',
            'transactions',
        ];

        $missingTables = array_values(array_filter(
            $requiredTables,
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        if ($missingTables !== []) {
            $this->warn('Missing tables: '.implode(', ', $missingTables));
            $this->info('Running migrations...');

            $migrateCode = Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $this->output->write(Artisan::output());

            if ($migrateCode !== self::SUCCESS) {
                $this->error('Migration failed.');

                return self::FAILURE;
            }
        } else {
            $this->info('All required tables already exist.');
        }

        $this->info('Running testing seeders...');

        $seedCode = Artisan::call('db:seed', [
            '--class' => DummyDatabaseSeeder::class,
            '--force' => true,
            '--no-interaction' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($seedCode !== self::SUCCESS) {
            $this->error('Seeding failed.');

            return self::FAILURE;
        }

        $this->info('Dummy database initialized successfully.');

        return self::SUCCESS;
    }
}
