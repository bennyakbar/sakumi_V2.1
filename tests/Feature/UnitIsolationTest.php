<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\SchoolClass;
use App\Models\Settlement;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentObligation;
use App\Models\Transaction;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $miUser;
    private User $raUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->mi = Unit::where('code', 'MI')->first();
        $this->ra = Unit::where('code', 'RA')->first();

        $this->miUser = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->miUser->assignRole('super_admin');

        $this->raUser = User::factory()->create(['unit_id' => $this->ra->id]);
        $this->raUser->assignRole('super_admin');
    }

    private function actAsUnit(User $user, Unit $unit): self
    {
        return $this->actingAs($user)->withSession(['current_unit_id' => $unit->id]);
    }

    // ─── Data Isolation ────────────────────────────────────────────

    public function test_student_list_only_shows_own_unit(): void
    {
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $miCat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);

        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $miClass->id,
            'category_id' => $miCat->id,
        ]);
        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => $raClass->id,
            'category_id' => $raCat->id,
        ]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->get(route('master.students.index'))
            ->assertOk()
            ->assertSee($raStudent->name)
            ->assertDontSee($miStudent->name);
    }

    public function test_cannot_access_other_unit_student_by_id(): void
    {
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $miCat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => $miClass->id,
            'category_id' => $miCat->id,
        ]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->get(route('master.students.show', $miStudent->id))
            ->assertNotFound();
    }

    public function test_cannot_create_student_with_other_unit_class(): void
    {
        $miClass = SchoolClass::factory()->create(['unit_id' => $this->mi->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('master.students.store'), [
                'nis' => '12345',
                'name' => 'Test Student',
                'class_id' => $miClass->id,
                'category_id' => $raCat->id,
                'gender' => 'L',
                'status' => 'active',
                'enrollment_date' => '2026-01-01',
            ])
            ->assertSessionHasErrors('class_id');
    }

    public function test_cannot_create_student_with_other_unit_category(): void
    {
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $miCat = StudentCategory::factory()->create(['unit_id' => $this->mi->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('master.students.store'), [
                'nis' => '12345',
                'name' => 'Test Student',
                'class_id' => $raClass->id,
                'category_id' => $miCat->id,
                'gender' => 'L',
                'status' => 'active',
                'enrollment_date' => '2026-01-01',
            ])
            ->assertSessionHasErrors('category_id');
    }

    // ─── Transaction Isolation ──────────────────────────────────────

    public function test_transaction_list_scoped_to_unit(): void
    {
        $miTx = Transaction::factory()->create(['unit_id' => $this->mi->id]);
        $raTx = Transaction::factory()->create(['unit_id' => $this->ra->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee($raTx->transaction_number)
            ->assertDontSee($miTx->transaction_number);
    }

    // ─── Invoice Isolation ──────────────────────────────────────────

    public function test_cannot_access_other_unit_invoice(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $miInvoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $miStudent->id,
        ]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->get(route('invoices.show', $miInvoice->id))
            ->assertNotFound();
    }

    // ─── Settlement Isolation ───────────────────────────────────────

    public function test_cannot_access_other_unit_settlement(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $miSettlement = Settlement::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $miStudent->id,
        ]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->get(route('settlements.show', $miSettlement->id))
            ->assertNotFound();
    }

    // ─── Authorization ──────────────────────────────────────────────

    public function test_middleware_sets_unit_from_user_when_session_empty(): void
    {
        // User has unit_id, but session is fresh (no current_unit_id)
        // Middleware should auto-set it from user.unit_id
        $this->actingAs($this->miUser)
            ->withSession([])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertEquals($this->mi->id, session('current_unit_id'));
    }

    public function test_login_sets_unit_session(): void
    {
        $user = User::factory()->create([
            'unit_id' => $this->mi->id,
            'password' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $this->assertEquals($this->mi->id, session('current_unit_id'));
    }

    // ─── Cross-Unit Validation ──────────────────────────────────────

    public function test_settlement_rejects_cross_unit_invoice(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $miInvoice = Invoice::factory()->create([
            'unit_id' => $this->mi->id,
            'student_id' => $miStudent->id,
            'status' => 'unpaid',
        ]);

        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->ra->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->ra->id])->id,
        ]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('settlements.store'), [
                'student_id' => $raStudent->id,
                'invoice_id' => $miInvoice->id,
                'payment_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'amount' => 100000,
            ])
            ->assertSessionHasErrors('invoice_id');
    }

    public function test_transaction_rejects_cross_unit_student(): void
    {
        $miStudent = Student::factory()->create([
            'unit_id' => $this->mi->id,
            'class_id' => SchoolClass::factory()->create(['unit_id' => $this->mi->id])->id,
            'category_id' => StudentCategory::factory()->create(['unit_id' => $this->mi->id])->id,
        ]);
        $raFeeType = FeeType::factory()->create(['unit_id' => $this->ra->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('transactions.store'), [
                'student_id' => $miStudent->id,
                'transaction_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    ['fee_type_id' => $raFeeType->id, 'amount' => 50000],
                ],
            ])
            ->assertSessionHasErrors('student_id');
    }

    public function test_transaction_rejects_cross_unit_fee_type(): void
    {
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);
        $raStudent = Student::factory()->create([
            'unit_id' => $this->ra->id,
            'class_id' => $raClass->id,
            'category_id' => $raCat->id,
        ]);
        $miFeeType = FeeType::factory()->create(['unit_id' => $this->mi->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('transactions.store'), [
                'student_id' => $raStudent->id,
                'transaction_date' => today()->toDateString(),
                'payment_method' => 'cash',
                'items' => [
                    ['fee_type_id' => $miFeeType->id, 'amount' => 50000],
                ],
            ])
            ->assertSessionHasErrors('items.0.fee_type_id');
    }

    // ─── Creating records assigns correct unit ──────────────────────

    public function test_created_student_gets_current_unit(): void
    {
        $raClass = SchoolClass::factory()->create(['unit_id' => $this->ra->id]);
        $raCat = StudentCategory::factory()->create(['unit_id' => $this->ra->id]);

        $this->actAsUnit($this->raUser, $this->ra)
            ->post(route('master.students.store'), [
                'nis' => 'RA001',
                'name' => 'RA Student',
                'class_id' => $raClass->id,
                'category_id' => $raCat->id,
                'gender' => 'P',
                'status' => 'active',
                'enrollment_date' => '2026-01-01',
            ]);

        $student = Student::withoutGlobalScope('unit')->where('nis', 'RA001')->first();
        $this->assertNotNull($student);
        $this->assertEquals($this->ra->id, $student->unit_id);
    }
}
