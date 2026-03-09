<?php

namespace App\Console\Commands;

use App\Models\Unit;
use App\Models\User;
use App\Services\InvoiceGenerationService;
use App\Services\UnitContext;
use Illuminate\Console\Command;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly
                            {--month= : Month number (1-12), defaults to current month}
                            {--year= : Year, defaults to current year}
                            {--unit= : Unit code (MI, RA, DTA). If omitted, runs for all active units.}
                            {--class= : Optional class ID to limit generation}';

    protected $description = 'Generate monthly invoices from active invoice templates';

    public function handle(InvoiceGenerationService $generationService): int
    {
        $month = (int) ($this->option('month') ?? now()->month);
        $year = (int) ($this->option('year') ?? now()->year);
        $classId = $this->option('class') ? (int) $this->option('class') : null;

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

        // Use the first super_admin as the system user for automated generation
        $systemUser = User::role('super_admin')->first();
        if (! $systemUser) {
            $this->error('No super_admin user found for system attribution.');
            return self::FAILURE;
        }

        $unitContext = app(UnitContext::class);
        $totalCreated = 0;
        $totalSkipped = 0;

        foreach ($units as $unit) {
            $unitContext->set($unit->id);
            $this->info("Generating monthly invoices for {$unit->code} ({$month}/{$year})...");

            $result = $generationService->generateMonthlyInvoices(
                $month, $year, $systemUser->id, $classId
            );

            $totalCreated += $result['created'];
            $totalSkipped += $result['skipped'];

            $this->info("  Created: {$result['created']}, Skipped: {$result['skipped']}");

            if (! empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->warn("  Error: {$error}");
                }
            }
        }

        $unitContext->clear();
        $this->info("Total: {$totalCreated} invoice(s) created, {$totalSkipped} skipped.");

        return self::SUCCESS;
    }
}
