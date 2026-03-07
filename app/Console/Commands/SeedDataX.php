<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SeedDataX extends Command
{
    protected $signature = 'seed:datax
                            {rows=100 : Number of rows to seed}
                            {--seeder=DataXSeeder : Seeder class (short or FQCN)}
                            {--env-key=SEED_DATAX_ROWS : Env key used by seeder for row count}';

    protected $description = 'Run a seeder with a configurable row count for pagination testing';

    public function handle(): int
    {
        $rows = (int) $this->argument('rows');
        $seederInput = (string) $this->option('seeder');
        $envKey = (string) $this->option('env-key');

        if ($rows < 1) {
            $this->error('Rows must be at least 1.');

            return self::FAILURE;
        }

        $seederClass = str_contains($seederInput, '\\')
            ? $seederInput
            : 'Database\\Seeders\\'.$seederInput;

        if (! class_exists($seederClass)) {
            $this->error("Seeder class not found: {$seederClass}");
            $this->line('Create it first with: php artisan make:seeder '.class_basename($seederClass));

            return self::FAILURE;
        }

        putenv("{$envKey}={$rows}");
        $_ENV[$envKey] = (string) $rows;
        $_SERVER[$envKey] = (string) $rows;

        $this->info("Seeding {$rows} rows via {$seederClass}...");

        $result = Artisan::call('db:seed', [
            '--class' => $seederClass,
            '--no-interaction' => true,
        ]);

        $this->output->write(Artisan::output());

        if ($result !== self::SUCCESS) {
            $this->error('Seeder failed.');

            return self::FAILURE;
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
