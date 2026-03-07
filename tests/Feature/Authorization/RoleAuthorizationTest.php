<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_super_admin_user_cannot_access_health_diagnostics(): void
    {
        $user = User::factory()->create();

        Role::firstOrCreate(['name' => 'bendahara']);
        $user->assignRole('bendahara');

        $response = $this->actingAs($user)->get('/health');

        $response->assertForbidden();
    }

    public function test_super_admin_user_can_access_health_diagnostics(): void
    {
        $user = User::factory()->create();

        Role::firstOrCreate(['name' => 'super_admin']);
        $user->assignRole('super_admin');

        $response = $this->actingAs($user)->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'checks', 'timestamp']);
    }
}
