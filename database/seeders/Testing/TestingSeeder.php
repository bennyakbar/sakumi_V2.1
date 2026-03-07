<?php

namespace Database\Seeders\Testing;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestingSeeder extends Seeder
{
    protected function ensureTestingEnvironment(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(static::class.' can only run in APP_ENV=testing');
        }

        // If DB_SAKUMI_MODE is set, it must be 'dummy' (null = PHPUnit, which is fine)
        $sakumiMode = env('DB_SAKUMI_MODE');
        if ($sakumiMode !== null && $sakumiMode !== 'dummy') {
            throw new RuntimeException(
                static::class.' requires DB_SAKUMI_MODE=dummy. Current: '.var_export($sakumiMode, true)
            );
        }

        // Console seeders don't always have a web session context, but unit-scoped
        // models rely on `current_unit_id` for automatic `unit_id` assignment.
        if (! session()->has('current_unit_id')) {
            $miId = DB::table('units')->where('code', 'MI')->value('id');
            if ($miId !== null) {
                session(['current_unit_id' => (int) $miId]);
            }
        }
    }
}
