<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Unit $mi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->mi = Unit::query()->where('code', 'MI')->firstOrFail();

        $this->superAdmin = User::factory()->create([
            'unit_id' => $this->mi->id,
            'is_active' => true,
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->actingAs($this->superAdmin);
        session(['current_unit_id' => $this->mi->id]);
    }

    public function test_users_index_can_be_rendered(): void
    {
        $this->get(route('users.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_user_with_role(): void
    {
        $response = $this->post(route('users.store'), [
            'name' => 'User Baru',
            'email' => 'baru@example.test',
            'unit_id' => $this->mi->id,
            'password' => 'Aa1!bcde2@',
            'password_confirmation' => 'Aa1!bcde2@',
            'role' => 'operator_tu',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'baru@example.test')->firstOrFail();
        $this->assertSame($this->mi->id, $user->unit_id);
        $this->assertTrue($user->hasRole('operator_tu'));
    }

    public function test_super_admin_can_update_user_and_deactivate(): void
    {
        $user = User::factory()->create([
            'unit_id' => $this->mi->id,
            'is_active' => true,
        ]);
        $user->assignRole('operator_tu');

        $this->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'unit_id' => $this->mi->id,
            'role' => 'bendahara',
            'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'is_active' => true,
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('bendahara'));

        $this->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_user_without_create_permission_cannot_access_create_page(): void
    {
        $operator = User::factory()->create(['unit_id' => $this->mi->id]);
        $operator->assignRole('operator_tu'); // users.view only

        $this->actingAs($operator)
            ->withSession(['current_unit_id' => $this->mi->id])
            ->get(route('users.create'))
            ->assertForbidden();
    }

    public function test_user_cannot_deactivate_self(): void
    {
        $this->delete(route('users.destroy', $this->superAdmin))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', [
            'id' => $this->superAdmin->id,
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_modify_own_role(): void
    {
        $this->put(route('users.update', $this->superAdmin), [
            'name' => $this->superAdmin->name,
            'email' => $this->superAdmin->email,
            'unit_id' => $this->mi->id,
            'role' => 'operator_tu',
            'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->superAdmin->refresh();
        $this->assertTrue($this->superAdmin->hasRole('super_admin'));
    }

    public function test_user_detail_page_can_be_rendered(): void
    {
        $user = User::factory()->create(['unit_id' => $this->mi->id]);

        $this->get(route('users.show', $user))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_admin_can_reset_user_password_and_get_temporary_password(): void
    {
        $user = User::factory()->create(['unit_id' => $this->mi->id]);
        $oldHash = $user->password;

        $response = $this->post(route('users.reset-password', $user));

        $response->assertRedirect();
        $response->assertSessionHas('temporary_password');

        $temporaryPassword = (string) $response->getSession()->get('temporary_password');
        $this->assertNotEmpty($temporaryPassword);

        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));
    }

    public function test_admin_can_bulk_activate_and_deactivate_users(): void
    {
        $u1 = User::factory()->create(['unit_id' => $this->mi->id, 'is_active' => true]);
        $u2 = User::factory()->create(['unit_id' => $this->mi->id, 'is_active' => true]);

        $this->post(route('users.bulk-status'), [
            'ids' => [$u1->id, $u2->id],
            'action' => 'deactivate',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $u1->id, 'is_active' => false]);
        $this->assertDatabaseHas('users', ['id' => $u2->id, 'is_active' => false]);

        $this->post(route('users.bulk-status'), [
            'ids' => [$u1->id, $u2->id],
            'action' => 'activate',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $u1->id, 'is_active' => true]);
        $this->assertDatabaseHas('users', ['id' => $u2->id, 'is_active' => true]);
    }

    public function test_users_can_be_exported_as_csv_with_filters(): void
    {
        $active = User::factory()->create([
            'unit_id' => $this->mi->id,
            'email' => 'active-user@example.test',
            'is_active' => true,
        ]);
        $inactive = User::factory()->create([
            'unit_id' => $this->mi->id,
            'email' => 'inactive-user@example.test',
            'is_active' => false,
        ]);

        $response = $this->get(route('users.export', ['status' => 'active']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('active-user@example.test', $content);
        $this->assertStringNotContainsString('inactive-user@example.test', $content);
    }
}
