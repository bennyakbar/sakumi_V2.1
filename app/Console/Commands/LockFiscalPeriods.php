<?php

namespace App\Console\Commands;

use App\Models\FiscalPeriod;
use App\Models\Unit;
use App\Services\UnitContext;
use Illuminate\Console\Command;

class LockFiscalPeriods extends Command
{
    protected $signature = 'fiscal:auto-lock
                            {--days=5 : Lock periods that ended more than N days ago}
                            {--unit= : Unit code (MI, RA, DTA). If omitted, runs for all active units.}';

    protected $description = 'Auto-lock fiscal periods that ended more than N days ago';

    public function handle(): int
    {
        $graceDays = (int) $this->option('days');
        $cutoff = now()->subDays($graceDays)->endOfDay();
        $unitCode = $this->option('unit');

        if ($unitCode) {
            $units = Unit::where('code', $unitCode)->where('is_active', true)->get();
            if ($units->isEmpty()) {
                $this->error("Unit '{$unitCode}' not found or inactive.");

                return self::FAILURE;
            }
        } else {
            $units = Unit::where('is_active', true)->get();
        }

        $totalLocked = 0;

        $unitContext = app(UnitContext::class);

        foreach ($units as $unit) {
            $unitContext->set($unit->id);

            $periods = FiscalPeriod::where('is_locked', false)
                ->where('ends_on', '<=', $cutoff)
                ->get();

            foreach ($periods as $period) {
                $period->update([
                    'is_locked' => true,
                    'locked_at' => now(),
                    'locked_by' => null, // system auto-lock
                    'notes' => trim(($period->notes ? $period->notes . "\n" : '') . "Auto-locked after {$graceDays}-day grace period."),
                ]);
                $totalLocked++;
                $this->info("  Locked {$period->period_key} for {$unit->code}.");
            }
        }

        $unitContext->clear();
        $this->info("Total: {$totalLocked} fiscal period(s) auto-locked.");

        return self::SUCCESS;
    }
}
