<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAcademicYearTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_can_update_academic_year_setting_with_valid_format(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        $this->put(route('settings.update'), [
            'academic_year_current' => '2026/2027',
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('2026/2027', (string) Setting::get('academic_year_current'));
    }

    public function test_update_rejects_non_consecutive_academic_year(): void
    {
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        $this->from(route('settings.edit'))
            ->put(route('settings.update'), [
                'academic_year_current' => '2026/2028',
            ])
            ->assertRedirect(route('settings.edit'))
            ->assertSessionHasErrors('academic_year_current');
    }
}
