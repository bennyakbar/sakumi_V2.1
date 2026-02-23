<?php

namespace Tests\Feature;

use App\Models\AccountingEvent;
use App\Models\Invoice;
use App\Models\JournalEntryV2;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingEngine;
use Database\Seeders\AccountMappingsSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingEngineV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['features.accounting_engine_v2' => true]);
    }

    public function test_it_posts_invoice_created_with_balanced_journal_entries(): void
    {
        [$unit, $user] = $this->prepareSeededUnitContext();

        app(AccountingEngine::class)->post('invoice.created', [
            'unit_id' => $unit->id,
            'source_type' => 'invoice',
            'source_id' => 101,
            'total_amount' => 250000,
            'effective_date' => '2026-02-23',
            'created_by' => $user->id,
            'idempotency_key' => 'test.invoice.created.101',
        ]);

        $event = AccountingEvent::query()->where('idempotency_key', 'test.invoice.created.101')->first();

        $this->assertNotNull($event);
        $this->assertSame('posted', $event->status);
        $this->assertSame('invoice.created', $event->event_type);

        $entries = JournalEntryV2::query()->where('accounting_event_id', $event->id)->get();
        $this->assertCount(2, $entries);

        $debit = (float) $entries->sum('debit');
        $credit = (float) $entries->sum('credit');
        $this->assertSame(250000.0, $debit);
        $this->assertSame(250000.0, $credit);

        $this->assertDatabaseHas('fiscal_periods', [
            'unit_id' => $unit->id,
            'period_key' => '2026-02',
            'is_locked' => 0,
        ]);
    }

    public function test_it_enforces_event_idempotency(): void
    {
        [$unit, $user] = $this->prepareSeededUnitContext();

        $payload = [
            'unit_id' => $unit->id,
            'source_type' => 'invoice',
            'source_id' => 102,
            'total_amount' => 120000,
            'effective_date' => '2026-02-23',
            'created_by' => $user->id,
            'idempotency_key' => 'test.invoice.created.102',
        ];

        app(AccountingEngine::class)->post('invoice.created', $payload);
        app(AccountingEngine::class)->post('invoice.created', $payload);

        $this->assertSame(1, AccountingEvent::query()->where('idempotency_key', 'test.invoice.created.102')->count());

        $eventId = AccountingEvent::query()
            ->where('idempotency_key', 'test.invoice.created.102')
            ->value('id');

        $this->assertSame(2, JournalEntryV2::query()->where('accounting_event_id', $eventId)->count());
    }

    public function test_it_stores_payment_allocations_for_settlement_applied(): void
    {
        [$unit, $user] = $this->prepareSeededUnitContext();
        $student = $this->createStudentInUnit($unit);

        $invoice = Invoice::factory()->create([
            'unit_id' => $unit->id,
            'student_id' => $student->id,
            'created_by' => $user->id,
            'total_amount' => 300000,
            'paid_amount' => 0,
            'status' => 'unpaid',
        ]);

        app(AccountingEngine::class)->post('settlement.applied', [
            'unit_id' => $unit->id,
            'source_type' => 'settlement',
            'source_id' => 201,
            'total_amount' => 125000,
            'effective_date' => '2026-02-23',
            'created_by' => $user->id,
            'allocations' => [
                ['invoice_id' => $invoice->id, 'amount' => 125000],
            ],
            'idempotency_key' => 'test.settlement.applied.201',
        ]);

        $eventId = AccountingEvent::query()
            ->where('idempotency_key', 'test.settlement.applied.201')
            ->value('id');

        $this->assertDatabaseHas('payment_allocations_v2', [
            'accounting_event_id' => $eventId,
            'unit_id' => $unit->id,
            'payment_source_type' => 'settlement',
            'payment_source_id' => 201,
            'invoice_id' => $invoice->id,
            'allocated_amount' => 125000,
        ]);
    }

    public function test_it_posts_reversal_from_previous_event_and_prevents_duplicate_reversal(): void
    {
        [$unit, $user] = $this->prepareSeededUnitContext();

        app(AccountingEngine::class)->post('settlement.applied', [
            'unit_id' => $unit->id,
            'source_type' => 'settlement',
            'source_id' => 301,
            'total_amount' => 150000,
            'effective_date' => '2026-02-23',
            'created_by' => $user->id,
            'idempotency_key' => 'test.settlement.applied.301',
        ]);

        $original = AccountingEvent::query()
            ->where('idempotency_key', 'test.settlement.applied.301')
            ->firstOrFail();

        app(AccountingEngine::class)->post('reversal.posted', [
            'unit_id' => $unit->id,
            'source_type' => 'settlement',
            'source_id' => 301,
            'effective_date' => '2026-02-24',
            'created_by' => $user->id,
            'reason' => 'Void settlement',
            'idempotency_key' => 'test.reversal.301',
        ]);

        app(AccountingEngine::class)->post('reversal.posted', [
            'unit_id' => $unit->id,
            'source_type' => 'settlement',
            'source_id' => 301,
            'effective_date' => '2026-02-24',
            'created_by' => $user->id,
            'reason' => 'Void settlement duplicate',
            'idempotency_key' => 'test.reversal.301.duplicate',
        ]);

        $reversalEvent = AccountingEvent::query()
            ->where('event_type', 'reversal.posted')
            ->where('reversal_of_event_id', $original->id)
            ->first();

        $this->assertNotNull($reversalEvent);
        $this->assertTrue($reversalEvent->is_reversal);
        $this->assertSame(1, AccountingEvent::query()->where('reversal_of_event_id', $original->id)->count());

        $this->assertDatabaseHas('reversals', [
            'unit_id' => $unit->id,
            'original_accounting_event_id' => $original->id,
            'reversal_accounting_event_id' => $reversalEvent->id,
        ]);

        $originalEntries = JournalEntryV2::query()
            ->where('accounting_event_id', $original->id)
            ->get()
            ->keyBy('account_id');

        $reversalEntries = JournalEntryV2::query()
            ->where('accounting_event_id', $reversalEvent->id)
            ->get()
            ->keyBy('account_id');

        $this->assertCount($originalEntries->count(), $reversalEntries);

        foreach ($originalEntries as $accountId => $line) {
            $this->assertTrue($reversalEntries->has($accountId));
            $reversedLine = $reversalEntries->get($accountId);
            $this->assertSame((float) $line->debit, (float) $reversedLine->credit);
            $this->assertSame((float) $line->credit, (float) $reversedLine->debit);
        }
    }

    private function prepareSeededUnitContext(): array
    {
        $unit = Unit::factory()->create();
        $user = User::factory()->create(['unit_id' => $unit->id]);

        $this->withSession(['current_unit_id' => $unit->id]);

        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(AccountMappingsSeeder::class);

        return [$unit, $user];
    }

    private function createStudentInUnit(Unit $unit): Student
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
