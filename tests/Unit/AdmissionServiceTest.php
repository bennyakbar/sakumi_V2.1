<?php

namespace Tests\Unit;

use App\Models\AdmissionPeriod;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Models\User;
use App\Services\AdmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_registration_number_skips_malformed_latest_number(): void
    {
        Carbon::setTestNow('2026-03-02 14:35:44');

        $unitId = 1;
        $user = User::factory()->create(['unit_id' => $unitId]);
        $class = SchoolClass::factory()->create(['unit_id' => $unitId]);
        $category = StudentCategory::factory()->create(['unit_id' => $unitId]);
        $period = AdmissionPeriod::create([
            'unit_id' => $unitId,
            'name' => 'Gelombang 1',
            'academic_year' => '2026/2027',
            'registration_open' => '2026-01-01',
            'registration_close' => '2026-06-30',
            'status' => 'open',
        ]);

        $basePayload = [
            'unit_id' => $unitId,
            'admission_period_id' => $period->id,
            'name' => 'Existing Applicant',
            'target_class_id' => $class->id,
            'category_id' => $category->id,
            'gender' => 'P',
            'status' => 'registered',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('applicants')->insert($basePayload + [
            'registration_number' => 'REG-MI-2026-0001',
            'deleted_at' => now(),
        ]);

        DB::table('applicants')->insert($basePayload + [
            'registration_number' => 'REG-MI-2026-INVALID',
        ]);

        $next = app(AdmissionService::class)->generateRegistrationNumber($unitId);

        $this->assertSame('REG-MI-2026-0002', $next);
    }
}
