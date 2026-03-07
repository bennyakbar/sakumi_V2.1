<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitSwitchTest extends TestCase
{
    use RefreshDatabase;

    private Unit $mi;
    private Unit $ra;
    private User $superAdmin;
    private User $bendahara;
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->mi = Unit::where('code', 'MI')->first();
        $this->ra = Unit::where('code', 'RA')->first();

        $this->superAdmin = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->superAdmin->assignRole('super_admin');

        $this->bendahara = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->bendahara->assignRole('bendahara');

        $this->operator = User::factory()->create(['unit_id' => $this->mi->id]);
        $this->operator->assignRole('operator_tu');
    }

    public function test_super_admin_can_switch_unit(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $this->ra->id])
            ->assertRedirect();

        $this->assertEquals($this->ra->id, session('current_unit_id'));
    }

    public function test_non_super_admin_cannot_switch_to_other_unit(): void
    {
        $this->actingAs($this->operator)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $this->ra->id])
            ->assertForbidden();

        $this->assertEquals($this->mi->id, session('current_unit_id'));
    }

    public function test_non_super_admin_can_post_own_unit(): void
    {
        $this->actingAs($this->operator)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $this->mi->id])
            ->assertRedirect();

        $this->assertEquals($this->mi->id, session('current_unit_id'));
    }

    public function test_bendahara_can_switch_unit(): void
    {
        $this->actingAs($this->bendahara)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $this->ra->id])
            ->assertRedirect();

        $this->assertEquals($this->ra->id, session('current_unit_id'));
    }

    public function test_cannot_switch_to_inactive_unit(): void
    {
        $inactive = Unit::factory()->create(['is_active' => false]);

        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $inactive->id])
            ->assertSessionHasErrors('unit_id');
    }

    public function test_cannot_switch_to_nonexistent_unit(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => 9999])
            ->assertSessionHasErrors('unit_id');
    }

    public function test_switching_scopes_subsequent_queries(): void
    {
        SchoolClass::factory()->create(['unit_id' => $this->mi->id, 'name' => 'MI-Only-Class']);
        SchoolClass::factory()->create(['unit_id' => $this->ra->id, 'name' => 'RA-Only-Class']);

        // Start as MI
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('master.classes.index'))
            ->assertSee('MI-Only-Class')
            ->assertDontSee('RA-Only-Class');

        // Switch to RA
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->post(route('unit.switch'), ['unit_id' => $this->ra->id]);

        // Now see RA data
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->ra->id])
            ->get(route('master.classes.index'))
            ->assertSee('RA-Only-Class')
            ->assertDontSee('MI-Only-Class');
    }

    public function test_nav_shows_unit_switcher_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->mi->code)
            ->assertSee($this->ra->code);
    }

    public function test_nav_shows_unit_switcher_for_bendahara(): void
    {
        $this->actingAs($this->bendahara)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->mi->code)
            ->assertSee($this->ra->code);
    }

    public function test_nav_hides_master_core_links_for_bendahara(): void
    {
        $this->actingAs($this->bendahara)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Students')
            ->assertDontSee('Classes')
            ->assertDontSee('Categories');
    }

    public function test_nav_shows_static_badge_for_non_super_admin(): void
    {
        $this->actingAs($this->operator)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($this->mi->code);
    }

    public function test_nav_hides_master_finance_links_for_operator(): void
    {
        $this->actingAs($this->operator)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Fee Types')
            ->assertDontSee('Fee Matrix');
    }

    public function test_guest_cannot_switch_unit(): void
    {
        $this->post(route('unit.switch'), ['unit_id' => $this->mi->id])
            ->assertRedirect('/login');
    }
}
