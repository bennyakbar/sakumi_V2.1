<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(UnitSeeder::class);
        $this->seed(RolePermissionSeeder::class);

        $this->unit = Unit::query()->where('code', 'MI')->firstOrFail();
        $this->superAdmin = User::factory()->create([
            'unit_id' => $this->unit->id,
        ]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_dashboard_route_is_rate_limited(): void
    {
        for ($i = 0; $i < 120; $i++) {
            $this->actingAsUnit()->get(route('dashboard'))
                ->assertOk();
        }

        $this->actingAsUnit()->get(route('dashboard'))
            ->assertStatus(429);
    }

    public function test_reports_daily_route_is_rate_limited(): void
    {
        $query = ['date' => now()->toDateString()];

        for ($i = 0; $i < 60; $i++) {
            $this->actingAsUnit()->get(route('reports.daily', $query))
                ->assertOk();
        }

        $this->actingAsUnit()->get(route('reports.daily', $query))
            ->assertStatus(429);
    }

    private function actingAsUnit(): self
    {
        return $this->actingAs($this->superAdmin)
            ->withSession(['current_unit_id' => $this->unit->id]);
    }
}

