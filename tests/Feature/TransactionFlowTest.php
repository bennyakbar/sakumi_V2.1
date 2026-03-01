<?php

namespace Tests\Feature;

use App\Models\FeeType;
use App\Models\Invoice;
use App\Models\AccountingEvent;
use App\Models\Reversal;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentObligation;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReceiptService;
use App\Services\ReceiptVerificationService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\AccountMappingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_create_income_transaction_with_multiple_items(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $this->mock(ReceiptService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')->once()->andReturn('receipts/mock.pdf');
        });

        $feeA = FeeType::query()->create([
            'code' => 'SPP',
            'name' => 'SPP Februari',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $feeB = FeeType::query()->create([
            'code' => 'BOOK',
            'name' => 'Buku Paket',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $response = $this->post(route('transactions.store'), [
            'transaction_date' => '2026-02-14',
            'payment_method' => 'cash',
            'description' => 'Pembayaran gabungan',
            'items' => [
                ['fee_type_id' => $feeA->id, 'amount' => 100000, 'description' => 'SPP Februari'],
                ['fee_type_id' => $feeB->id, 'amount' => 50000, 'description' => 'Buku semester'],
            ],
        ]);

        $transaction = Transaction::query()->firstOrFail();

        $response->assertRedirect(route('transactions.show', $transaction));

        $this->assertSame('income', $transaction->type);
        $this->assertSame('completed', $transaction->status);
        $this->assertSame('NF-2026-000001', $transaction->transaction_number);
        $this->assertSame('150000.00', $transaction->total_amount);
        $this->assertNull($transaction->student_id);

        $this->assertDatabaseCount('transaction_items', 2);
        $this->assertDatabaseHas('transaction_items', [
            'transaction_id' => $transaction->id,
            'fee_type_id' => $feeA->id,
            'amount' => '100000.00',
        ]);
    }

    public function test_receipt_print_page_uses_a5_landscape_layout_with_signature_block(): void
    {
        $user = User::factory()->create(['name' => 'Admin TU']);
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $class = SchoolClass::query()->create([
            'name' => '2A',
            'level' => 2,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);

        $category = StudentCategory::query()->create([
            'code' => 'VIP',
            'name' => 'VIP',
            'discount_percentage' => 0,
        ]);

        $student = Student::query()->create([
            'nis' => '10002',
            'nisn' => '20002',
            'name' => 'Siti',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::query()->create([
            'code' => 'REG',
            'name' => 'Daftar Ulang',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_number' => 'NF-2026-000010',
            'transaction_date' => '2026-02-14',
            'type' => 'income',
            'student_id' => $student->id,
            'payment_method' => 'transfer',
            'total_amount' => 250000,
            'description' => 'Pembayaran registrasi',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $transaction->items()->create([
            'fee_type_id' => $feeType->id,
            'amount' => 250000,
            'description' => 'Registrasi ulang',
        ]);

        $this->get(route('receipts.print', $transaction))
            ->assertOk()
            ->assertSee('A5 landscape', false)
            ->assertSee('RECEIPT PEMBAYARAN', false)
            ->assertSee('Digital Signature', false)
            ->assertSee('Admin TU', false);
    }

    public function test_expense_print_page_uses_expense_layout_without_student_block(): void
    {
        $user = User::factory()->create(['name' => 'Admin TU']);
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $feeType = FeeType::query()->create([
            'code' => 'EXP-TEST',
            'name' => 'Biaya Operasional',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_number' => 'NK-2026-000010',
            'transaction_date' => '2026-02-14',
            'type' => 'expense',
            'student_id' => null,
            'payment_method' => 'cash',
            'total_amount' => 120000,
            'description' => 'Pengeluaran operasional',
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $transaction->items()->create([
            'fee_type_id' => $feeType->id,
            'amount' => 120000,
            'description' => 'Pembelian kebutuhan kantor',
        ]);

        $this->get(route('receipts.print', $transaction))
            ->assertOk()
            ->assertSee('BUKTI PENGELUARAN', false)
            ->assertSee('Total Pengeluaran', false)
            ->assertDontSee('Nama Siswa', false)
            ->assertDontSee('Kelas', false);
    }

    public function test_income_transaction_redirects_to_settlement_when_student_has_active_invoice(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $class = SchoolClass::query()->create([
            'name' => '1B',
            'level' => 1,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);

        $category = StudentCategory::query()->create([
            'code' => 'BLK',
            'name' => 'Block Test',
            'discount_percentage' => 0,
        ]);

        $student = Student::query()->create([
            'nis' => '99001',
            'nisn' => '99002',
            'name' => 'Student With Invoice',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $feeType = FeeType::query()->create([
            'code' => 'BLK-FEE',
            'name' => 'Blocked Fee',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $invoice = Invoice::factory()->create([
            'unit_id' => $user->unit_id,
            'student_id' => $student->id,
            'status' => 'unpaid',
            'paid_amount' => 0,
            'created_by' => $user->id,
        ]);

        $response = $this->post(route('transactions.store'), [
            'student_id' => $student->id,
            'transaction_date' => '2026-02-17',
            'payment_method' => 'cash',
            'items' => [
                ['fee_type_id' => $feeType->id, 'amount' => 100000, 'description' => 'Test'],
            ],
        ]);

        $response->assertRedirect(route('settlements.create', [
            'student_id' => $student->id,
            'invoice_id' => $invoice->id,
        ]));
        $response->assertSessionHas('status');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_receipt_verification_endpoint_returns_valid_for_correct_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $class = SchoolClass::query()->create([
            'name' => '3A',
            'level' => 3,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);

        $category = StudentCategory::query()->create([
            'code' => 'TST',
            'name' => 'Test',
            'discount_percentage' => 0,
        ]);

        $student = Student::query()->create([
            'nis' => '30001',
            'nisn' => '30002',
            'name' => 'Verifikasi',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $transaction = Transaction::query()->create([
            'transaction_number' => 'NF-2026-000099',
            'transaction_date' => '2026-02-15',
            'type' => 'income',
            'student_id' => $student->id,
            'payment_method' => 'cash',
            'total_amount' => 100000,
            'status' => 'completed',
            'created_by' => $user->id,
        ]);

        $code = app(ReceiptVerificationService::class)->makeCode($transaction);

        $this->get(route('receipts.verify', ['transactionNumber' => $transaction->transaction_number, 'code' => $code]))
            ->assertOk()
            ->assertSee('DOKUMEN VALID', false)
            ->assertSee($transaction->transaction_number, false)
            ->assertSee($code, false);
    }

    public function test_cancel_income_transaction_posts_accounting_reversal_when_engine_enabled(): void
    {
        // Accounting engine is now always enabled (mandatory).
        Setting::set('academic_year_current', '2025/2026');

        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(AccountMappingsSeeder::class);

        $this->mock(ReceiptService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')->once()->andReturn('receipts/mock.pdf');
            $mock->shouldReceive('generateCancelled')->once()->andReturn('receipts/mock-cancelled.pdf');
        });

        $feeType = FeeType::query()->create([
            'code' => 'REV-FEE',
            'name' => 'Reversal Fee',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $createResponse = $this->post(route('transactions.store'), [
            'transaction_date' => '2026-02-18',
            'payment_method' => 'cash',
            'description' => 'Pembayaran untuk uji reversal',
            'items' => [
                ['fee_type_id' => $feeType->id, 'amount' => 123000, 'description' => 'Uji'],
            ],
        ]);

        $transaction = Transaction::query()->firstOrFail();
        $createResponse->assertRedirect(route('transactions.show', $transaction));

        $this->delete(route('transactions.destroy', $transaction), [
            'cancellation_reason' => 'Test cancel with reversal',
        ])->assertRedirect(route('transactions.index'));

        $transaction->refresh();
        $this->assertSame('cancelled', $transaction->status);

        $original = AccountingEvent::query()
            ->where('source_type', 'transaction')
            ->where('source_id', $transaction->id)
            ->where('is_reversal', false)
            ->first();
        $reversal = AccountingEvent::query()
            ->where('source_type', 'transaction')
            ->where('source_id', $transaction->id)
            ->where('is_reversal', true)
            ->where('event_type', 'reversal.posted')
            ->first();

        $this->assertNotNull($original);
        $this->assertNotNull($reversal);
        $this->assertDatabaseHas('reversals', [
            'original_accounting_event_id' => $original->id,
            'reversal_accounting_event_id' => $reversal->id,
        ]);
        $this->assertSame(1, Reversal::query()->where('original_accounting_event_id', $original->id)->count());
    }

    public function test_income_with_monthly_fee_requires_student_selection(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $monthlyFee = FeeType::query()->create([
            'code' => 'SPP-REQ-STUDENT',
            'name' => 'SPP',
            'is_monthly' => true,
            'is_active' => true,
        ]);

        $this->post(route('transactions.store'), [
            'transaction_date' => '2026-02-20',
            'payment_method' => 'cash',
            'items' => [
                ['fee_type_id' => $monthlyFee->id, 'amount' => 100000, 'description' => 'SPP'],
            ],
        ])->assertSessionHasErrors('student_id');
    }

    public function test_income_with_student_and_unpaid_obligation_is_blocked(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $class = SchoolClass::query()->create([
            'name' => '2B',
            'level' => 2,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'STD',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);
        $student = Student::query()->create([
            'nis' => '22001',
            'nisn' => '22002',
            'name' => 'Unpaid Obligation Student',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $monthlyFee = FeeType::query()->create([
            'code' => 'SPP-BLOCK',
            'name' => 'SPP Block',
            'is_monthly' => true,
            'is_active' => true,
        ]);

        StudentObligation::query()->create([
            'unit_id' => $user->unit_id,
            'student_id' => $student->id,
            'fee_type_id' => $monthlyFee->id,
            'month' => 2,
            'year' => 2026,
            'amount' => 120000,
            'is_paid' => false,
            'paid_amount' => 0,
        ]);

        $this->post(route('transactions.store'), [
            'student_id' => $student->id,
            'transaction_date' => '2026-02-20',
            'payment_method' => 'cash',
            'items' => [
                ['fee_type_id' => $monthlyFee->id, 'amount' => 120000, 'description' => 'SPP Feb'],
            ],
        ])->assertSessionHasErrors('student_id');
    }

    public function test_income_with_student_and_monthly_fee_still_must_use_invoice_flow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('bendahara');
        $this->actingAs($user);
        session(['current_unit_id' => $user->unit_id]);

        $class = SchoolClass::query()->create([
            'name' => '3B',
            'level' => 3,
            'academic_year' => '2025/2026',
            'is_active' => true,
        ]);
        $category = StudentCategory::query()->create([
            'code' => 'RGL',
            'name' => 'Regular',
            'discount_percentage' => 0,
        ]);
        $student = Student::query()->create([
            'nis' => '33001',
            'nisn' => '33002',
            'name' => 'Monthly Walkin Blocked',
            'class_id' => $class->id,
            'category_id' => $category->id,
            'status' => 'active',
        ]);

        $monthlyFee = FeeType::query()->create([
            'code' => 'SPP-INVOICE-ONLY',
            'name' => 'SPP Invoice Only',
            'is_monthly' => true,
            'is_active' => true,
        ]);

        $this->post(route('transactions.store'), [
            'student_id' => $student->id,
            'transaction_date' => '2026-02-20',
            'payment_method' => 'cash',
            'items' => [
                ['fee_type_id' => $monthlyFee->id, 'amount' => 110000, 'description' => 'SPP Feb'],
            ],
        ])->assertSessionHasErrors('student_id');

        $this->assertDatabaseCount('transactions', 0);
    }
}
