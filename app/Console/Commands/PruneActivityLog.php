<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneActivityLog extends Command
{
    protected $signature = 'activitylog:prune
                            {--days= : Delete records older than N days (default: config value)}';

    protected $description = 'Delete activity log records older than the configured retention period';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('activitylog.delete_records_older_than_days', 365));
        $cutoff = now()->subDays($days);

        $this->info("Pruning activity log records older than {$days} days (before {$cutoff->toDateString()})...");

        $deleted = DB::table(config('activitylog.table_name', 'activity_log'))
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} old activity log record(s).");

        return self::SUCCESS;
    }
}
