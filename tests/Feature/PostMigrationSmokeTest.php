<?php

namespace Tests\Feature;

use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Settlement;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostMigrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $miAdmin;
    private User $raAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->mi = Unit::where('code', 'MI')->first();
        $this->ra = Unit::where('code', 'RA')->first();

        $this->miAdmin = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->miAdmin->assignRole('super_admin');

        $this->raAdmin = User::factory()->create(['unit_id' => $this->ra->id]);
        $this->raAdmin->assignRole('super_admin');
    }

    private function actAsUnit(User $user, Unit $unit): self
    {
        return $this->actingAs($user)->withSession(['current_unit_id' => $unit->id]);
    }

    // ─── 1. Login sets unit session ─────────────────────────────────

    public function test_login_sets_unit_in_session(): void
    {
        $user = User::factory()->create([
            'unit_id' => $this->mi->id,
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertEquals($this->mi->id, session('current_unit_id'));
    }

    // ─── 2. Create student → auto-assigned to unit ──────────────────

    public function test_create_student_assigned_to_current_unit(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $cat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);

        $this->actAsUnit($this->miAdmin, $this->mi)
            ->post(route('master.students.store'), [
                'nis' => 'MI-001',
                'name' => 'Ahmad MI',
                'class_id' => $class->id,
                'category_id' => $cat->id,
                'gender' => 'L',
                'status' => 'active',
                'enrollment_date' => '2026-01-01',
            ])
            ->assertRedirect(route('master.students.index'));

        $student = Student::withoutGlobalScope('unit')->where('nis', 'MI-001')->first();
        $this->assertNotNull($student);
        $this->assertEquals($this->mi->id, $student->unit_id);
    }

    // ─── 3. Create transaction → correct unit + number generation ───

    public function test_create_transaction_with_correct_unit_and_number(): void
    {
        $class = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $cat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $student = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $class->id,
            'category_id' => $cat->id,
        ]);
        $feeType = FeeType::factory()->create([
            'unit_id' => $this->mi->id,
            'is_monthly' => false,
        ]);

        $this->actAsUnit($this->miAdmin, $this->mi)
            ->post(route('transactions.store'), [
                'student_id' => $student->id,
                'transaction_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    ['fee_type_id' => $feeType->id, 'amount' => 100000],
                ],
            ])
            ->assertRedirect();

        $tx = Transaction::withoutGlobalScope('unit')->latest('id')->first();
        $this->assertNotNull($tx);
        $this->assertEquals($this->mi->id, $tx->unit_id);
        $this->assertStringStartsWith('NF-', $tx->transaction_number);
    }

    // ─── 4. Number generation increments across units ───────────────

    public function test_number_generation_no_collision_across_units(): void
    {
        // Create TX in MI unit
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $miCat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $miClass->id,
            'category_id' => $miCat->id,
        ]);
        $miFee = FeeType::factory()->create([
            'unit_id' => $this->mi->id,
            'is_monthly' => false,
        ]);

        $this->actAsUnit($this->miAdmin, $this->mi)
            ->post(route('transactions.store'), [
                'student_id' => $miStudent->id,
                'transaction_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    ['fee_type_id' => $miFee->id, 'amount' => 50000],
                ],
            ])
            ->assertRedirect();

        // Create TX in RA unit
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);
        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => $raClass->id,
            'category_id' => $raCat->id,
        ]);
        $raFee = FeeType::factory()->create([
            'unit_id' => $this->ra->id,
            'is_monthly' => false,
        ]);

        $this->actAsUnit($this->raAdmin, $this->ra)
            ->post(route('transactions.store'), [
                'student_id' => $raStudent->id,
                'transaction_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    ['fee_type_id' => $raFee->id, 'amount' => 75000],
                ],
            ])
            ->assertRedirect();

        // Both should exist with different numbers
        $txAll = Transaction::withoutGlobalScope('unit')->orderBy('id')->get();
        $this->assertCount(2, $txAll);
        $this->assertNotEquals($txAll[0]->transaction_number, $txAll[1]->transaction_number);
        $this->assertEquals('NF-' . now()->year . '-000001', $txAll[0]->transaction_number);
        $this->assertEquals('NF-' . now()->year . '-000002', $txAll[1]->transaction_number);
    }

    // ─── 5. List pages only show same-unit data ─────────────────────

    public function test_list_pages_show_only_current_unit_data(): void
    {
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id, 'name' => 'MI-Class-1A']);
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id, 'name' => 'RA-Class-1B']);

        // MI user sees only MI class
        $this->actAsUnit($this->miAdmin, $this->mi)
            ->get(route('master.classes.index'))
            ->assertOk()
            ->assertSee('MI-Class-1A')
            ->assertDontSee('RA-Class-1B');

        // RA user sees only RA class
        $this->actAsUnit($this->raAdmin, $this->ra)
            ->get(route('master.classes.index'))
            ->assertOk()
            ->assertSee('RA-Class-1B')
            ->assertDontSee('MI-Class-1A');
    }

    // ─── 6. Invoice creation scoped to unit ─────────────────────────

    public function test_invoice_list_scoped_to_unit(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->ra->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->ra->id])->id,
        ]);

        $miInv = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $miStudent->id,
        ]);
        $raInv = Invoice::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $raStudent->id,
        ]);

        $this->actAsUnit($this->miAdmin, $this->mi)
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee($miInv->invoice_number)
            ->assertDontSee($raInv->invoice_number);
    }

    // ─── 7. Settlement list scoped to unit ──────────────────────────

    public function test_settlement_list_scoped_to_unit(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->ra->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->ra->id])->id,
        ]);

        $miStl = Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $miStudent->id,
        ]);
        $raStl = Settlement::factory()->create([
            'unit_id' => $this->ra->id,
            'student_id' => $raStudent->id,
        ]);

        $this->actAsUnit($this->miAdmin, $this->mi)
            ->get(route('settlements.index'))
            ->assertOk()
            ->assertSee($miStl->settlement_number)
            ->assertDontSee($raStl->settlement_number);
    }
}
