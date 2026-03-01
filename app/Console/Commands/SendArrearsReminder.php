<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\StudentObligation;
use App\Models\Unit;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendArrearsReminder extends Command
{
    protected $signature = 'arrears:remind
                            {--unit= : Unit code (MI, RA, DTA). If omitted, runs for all active units.}';

    protected $description = 'Send WhatsApp reminders to students with arrears exceeding threshold';

    public function handle(WhatsAppService $whatsAppService): int
    {
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

        $totalSent = 0;

        foreach ($units as $unit) {
            session(['current_unit_id' => $unit->id]);
            $this->info("Processing unit: {$unit->code}");

            $sent = $this->sendRemindersForUnit($whatsAppService);
            $totalSent += $sent;
            $this->info("  Sent {$sent} reminder(s) for {$unit->code}.");
        }

        session()->forget('current_unit_id');
        $this->info("Total: {$totalSent} arrears reminder(s) sent.");

        return self::SUCCESS;
    }

    private function sendRemindersForUnit(WhatsAppService $whatsAppService): int
    {
        $thresholdMonths = (int) getSetting('arrears_threshold_months', 1);

        $studentsWithArrears = Student::where('status', 'active')
            ->whereNotNull('parent_whatsapp')
            ->whereHas('obligations', fn ($q) => $q->where('is_paid', false))
            ->with(['obligations' => fn ($q) => $q->where('is_paid', false)->with('feeType')])
            ->get();

        $sent = 0;

        foreach ($studentsWithArrears as $student) {
            $unpaidCount = $student->obligations->count();
            if ($unpaidCount < $thresholdMonths) {
                continue;
            }

            $totalArrears = $student->obligations->sum('amount');
            $feeTypes = $student->obligations->pluck('feeType.name')->unique()->implode(', ');

            $notification = $whatsAppService->sendArrearsReminder($student, $feeTypes, $totalArrears);
            if ($notification) {
                $sent++;
            }
        }

        return $sent;
    }
}
