<?php

namespace Tests\Unit;

use App\Models\Unit;
use App\Services\SchoolIdentityService;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolIdentityServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(UnitSeeder::class);
    }

    public function test_it_uses_unit_name_when_unit_specific_school_name_is_missing(): void
    {
        $ra = Unit::query()->where('code', 'RA')->firstOrFail();

        setSetting('school_name', 'Madrasah Ibtidaiyah');
        setSetting('school_address', "Jl. Contoh A<br>Bandung");

        $identity = app(SchoolIdentityService::class)->resolve($ra->id);

        $this->assertSame('Raudhatul Athfal (RA)', $identity['school_name']);
        $this->assertSame("Jl. Contoh A\nBandung", $identity['school_address']);
    }
}
