<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshReportViews extends Command
{
    protected $signature = 'reports:refresh-views';

    protected $description = 'Concurrently refresh materialized views used by reports';

    public function handle(): int
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->warn('Materialized views are not supported on SQLite.');

            return self::SUCCESS;
        }

        $views = [
            'mv_ar_outstanding',
            'mv_daily_cash_summary',
        ];

        foreach ($views as $view) {
            $this->info("Refreshing {$view}...");
            DB::statement("REFRESH MATERIALIZED VIEW CONCURRENTLY {$view}");
        }

        $this->info('All materialized views refreshed.');

        return self::SUCCESS;
    }
}
