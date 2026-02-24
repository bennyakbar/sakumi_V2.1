<?php

namespace Tests\Unit;

use App\Models\SchoolClass;
use App\Models\Settlement;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReportService;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $miUser;
    private User $raUser;
    private Student $miStudent;
    private Student $raStudent;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(UnitSeeder::class);

        $this->mi = Unit::query()->where('code', 'MI')->firstOrFail();
        $this->ra = Unit::query()->where('code', 'RA')->firstOrFail();

        $this->miUser = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->raUser = User::factory()->create(['unit_id' => $this->ra->id]);

        $this->miStudent = $this->createStudentForUnit($this->mi);
        $this->raStudent = $this->createStudentForUnit($this->ra);
    }

    public function test_get_chart_data_respects_unit_scope(): void
    {
        session(['current_unit_id' => $this->mi->id]);

        Transaction::factory()->create([
            'unit_id' => $this->mi->id,
            'created_by' => $this->miUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'income',
            'student_id' => null,
            'total_amount' => 100000,
        ]);
        Transaction::factory()->create([
            'unit_id' => $this->mi->id,
            'created_by' => $this->miUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'expense',
            'student_id' => null,
            'total_amount' => 40000,
        ]);
        Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'created_by' => $this->miUser->id,
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
            'allocated_amount' => 60000,
            'total_amount' => 60000,
        ]);

        Transaction::factory()->create([
            'unit_id' => $this->ra->id,
            'created_by' => $this->raUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'income',
            'student_id' => null,
            'total_amount' => 300000,
        ]);
        Transaction::factory()->create([
            'unit_id' => $this->ra->id,
            'created_by' => $this->raUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'expense',
            'student_id' => null,
            'total_amount' => 100000,
        ]);
        Settlement::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $this->raStudent->id,
            'created_by' => $this->raUser->id,
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
            'allocated_amount' => 80000,
            'total_amount' => 80000,
        ]);

        $data = app(ReportService::class)->getChartData(1, false);

        $this->assertCount(1, $data['labels']);
        $this->assertSame(160000.0, $data['incomeData'][0]);
        $this->assertSame(40000.0, $data['expenseData'][0]);
    }

    public function test_get_chart_data_consolidated_includes_all_units(): void
    {
        session(['current_unit_id' => $this->mi->id]);

        Transaction::factory()->create([
            'unit_id' => $this->mi->id,
            'created_by' => $this->miUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'income',
            'student_id' => null,
            'total_amount' => 100000,
        ]);
        Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $this->miStudent->id,
            'created_by' => $this->miUser->id,
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
            'allocated_amount' => 60000,
            'total_amount' => 60000,
        ]);
        Transaction::factory()->create([
            'unit_id' => $this->ra->id,
            'created_by' => $this->raUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'income',
            'student_id' => null,
            'total_amount' => 300000,
        ]);
        Settlement::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $this->raStudent->id,
            'created_by' => $this->raUser->id,
            'payment_date' => now()->toDateString(),
            'status' => 'completed',
            'allocated_amount' => 80000,
            'total_amount' => 80000,
        ]);
        Transaction::factory()->create([
            'unit_id' => $this->ra->id,
            'created_by' => $this->raUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'expense',
            'student_id' => null,
            'total_amount' => 100000,
        ]);
        Transaction::factory()->create([
            'unit_id' => $this->mi->id,
            'created_by' => $this->miUser->id,
            'transaction_date' => now()->toDateString(),
            'status' => 'completed',
            'type' => 'expense',
            'student_id' => null,
            'total_amount' => 40000,
        ]);

        $data = app(ReportService::class)->getChartData(1, true);

        $this->assertCount(1, $data['labels']);
        $this->assertSame(540000.0, $data['incomeData'][0]);
        $this->assertSame(140000.0, $data['expenseData'][0]);
    }

    private function createStudentForUnit(Unit $unit): Student
    {
        $class = SchoolClass::factory()->create(['unit_id' => $unit->id]);
        $category = StudentCategory::factory()->create(['unit_id' => $unit->id]);

        return Student::factory()->create([
            'unit_id' => $unit->id,
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);
    }
}

