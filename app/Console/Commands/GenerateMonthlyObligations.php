<?php

namespace App\Console\Commands;

use App\Events\ObligationGenerated;
use App\Models\Unit;
use App\Services\ArrearsService;
use App\Services\UnitContext;
use Illuminate\Console\Command;

class GenerateMonthlyObligations extends Command
{
    protected $signature = 'obligations:generate
                            {--month= : Month number (1-12), defaults to current month}
                            {--year= : Year, defaults to current year}
                            {--unit= : Unit code (MI, RA, DTA). If omitted, runs for all active units.}';

    protected $description = 'Generate monthly student obligations based on fee matrix';

    public function handle(ArrearsService $arrearsService): int
    {
        $month = (int) ($this->option('month') ?? now()->month);
        $year = (int) ($this->option('year') ?? now()->year);

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

        $totalCreated = 0;

        $unitContext = app(UnitContext::class);

        foreach ($units as $unit) {
            $unitContext->set($unit->id);
            $this->info("Generating obligations for {$unit->code} {$month}/{$year}...");

            $count = $arrearsService->generateMonthlyObligations($month, $year);
            $totalCreated += $count;
            $this->info("  Created {$count} obligation(s) for {$unit->code}.");

            if ($count > 0) {
                ObligationGenerated::dispatch($month, $year, $count);
            }
        }

        $unitContext->clear();
        $this->info("Total: {$totalCreated} obligation(s) created.");

        return self::SUCCESS;
    }
}
