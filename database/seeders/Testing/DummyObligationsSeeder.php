<?php

namespace Database\Seeders\Testing;

use App\Services\ArrearsService;

class DummyObligationsSeeder extends TestingSeeder
{
    public function run(): void
    {
        $this->ensureTestingEnvironment();

        $arrearsService = app(ArrearsService::class);

        $year = (int) date('Y');
        $currentMonth = (int) date('m');
        $total = 0;

        // Generate obligations for Jan through current month
        for ($month = 1; $month <= $currentMonth; $month++) {
            $count = $arrearsService->generateMonthlyObligations($month, $year);
            $total += $count;
        }

        $this->command?->info("Generated {$total} obligations for {$year} (months 1-{$currentMonth}).");
    }
}
