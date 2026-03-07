<?php

namespace Tests\Feature;

use App\Models\FeeType;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_cashier_can_print_first_time_but_cannot_reprint(): void
    {
        $cashier = User::factory()->create();
        $cashier->assignRole('cashier');
        $this->actingAs($cashier);
        session(['current_unit_id' => $cashier->unit_id]);

        $feeType = FeeType::query()->create([
            'code' => 'REG',
            'name' => 'Registrasi',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_number' => 'NF-2026-100001',
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'cash',
            'total_amount' => 150000,
            'status' => 'completed',
            'created_by' => $cashier->id,
        ]);
        $transaction->items()->create([
            'fee_type_id' => $feeType->id,
            'amount' => 150000,
            'description' => 'Pembayaran',
        ]);

        $this->get(route('receipts.print', $transaction))
            ->assertOk()
            ->assertSee('ORIGINAL', false);

        $receipt = Receipt::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(1, $receipt->print_count);
        $this->assertDatabaseHas('receipt_print_logs', [
            'receipt_id' => $receipt->id,
            'user_id' => $cashier->id,
            'reason' => null,
        ]);

        $this->get(route('receipts.print', $transaction))
            ->assertForbidden();
    }

    public function test_bendahara_can_reprint_with_reason_and_public_verify(): void
    {
        $bendahara = User::factory()->create();
        $bendahara->assignRole('bendahara');
        $this->actingAs($bendahara);
        session(['current_unit_id' => $bendahara->unit_id]);

        $feeType = FeeType::query()->create([
            'code' => 'REG2',
            'name' => 'Registrasi 2',
            'is_monthly' => false,
            'is_active' => true,
        ]);

        $transaction = Transaction::query()->create([
            'transaction_number' => 'NF-2026-100002',
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'student_id' => null,
            'payment_method' => 'cash',
            'total_amount' => 180000,
            'status' => 'completed',
            'created_by' => $bendahara->id,
        ]);
        $transaction->items()->create([
            'fee_type_id' => $feeType->id,
            'amount' => 180000,
            'description' => 'Pembayaran',
        ]);

        $this->get(route('receipts.print', $transaction))->assertOk();
        $this->get(route('receipts.print', [
            'transaction' => $transaction,
            'reason_type' => 'lost',
        ]))
            ->assertOk()
            ->assertSee('COPY - Reprint #1', false);

        $receipt = Receipt::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(2, $receipt->print_count);
        $this->assertDatabaseHas('receipt_print_logs', [
            'receipt_id' => $receipt->id,
            'user_id' => $bendahara->id,
            'reason' => 'lost',
        ]);

        $this->get(route('receipts.verify.public', ['code' => $receipt->verification_code]))
            ->assertOk()
            ->assertSee('RECEIPT STATUS: VALID', false);
    }
}
