<?php

namespace Tests\Unit;

use App\Models\FeeMatrix;
use App\Models\FeeType;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Models\Unit;
use App\Services\FeeMatrixService;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeeMatrixServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitSeeder::class);
        $unit = Unit::where('code', 'MI')->first();
        session(['current_unit_id' => $unit->id]);
    }

    public function test_it_resolves_most_specific_fee_matrix(): void
    {
        $feeType = FeeType::query()->create([
            'code' => 'SPP',
            'name' => 'SPP',
            'is_monthly' => true,
            'is_active' => true,
        ]);
        $class = SchoolClass::query()->create([
            'name' => '1A',
            'level' => 1,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'REG',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);

        FeeMatrix::query()->create([
            'fee_type_id' => $feeType->id,
            'class_id' => null,
            'category_id' => null,
            'amount' => 100000,
            'effective_from' => '2025-01-01',
            'is_active' => true,
        ]);
        $mostSpecific = FeeMatrix::query()->create([
            'fee_type_id' => $feeType->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'amount' => 75000,
            'effective_from' => '2025-01-01',
            'is_active' => true,
        ]);

        $resolved = app(FeeMatrixService::class)->getFeeMatrix($feeType->id, $class->id, $category->id, now());

        $this->assertNotNull($resolved);
        $this->assertSame($mostSpecific->id, $resolved->id);
    }

    public function test_it_respects_effective_date_range(): void
    {
        $feeType = FeeType::query()->create([
            'code' => 'REG',
            'name' => 'Registration',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        FeeMatrix::query()->create([
            'fee_type_id' => $feeType->id,
            'class_id' => null,
            'category_id' => null,
            'amount' => 200000,
            'effective_from' => '2025-01-01',
            'effective_to' => '2025-06-30',
            'is_active' => true,
        ]);

        $resolved = app(FeeMatrixService::class)->getFeeMatrix($feeType->id, null, null, now()->setDate(2025, 12, 1));

        $this->assertNull($resolved);
    }
}
