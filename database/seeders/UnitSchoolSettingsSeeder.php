<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSchoolSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::query()->get(['code', 'name']);

        foreach ($units as $unit) {
            $code = strtolower((string) $unit->code);
            if ($code === '') {
                continue;
            }

            Setting::firstOrCreate(
                ['key' => "school_name_{$code}"],
                [
                    'value' => (string) $unit->name,
                    'type' => 'string',
                    'group' => 'school',
                    'description' => "Nama sekolah untuk unit {$unit->code}",
                ]
            );

            Setting::firstOrCreate(
                ['key' => "school_address_{$code}"],
                [
                    'value' => '',
                    'type' => 'string',
                    'group' => 'school',
                    'description' => "Alamat sekolah untuk unit {$unit->code}",
                ]
            );

            Setting::firstOrCreate(
                ['key' => "school_phone_{$code}"],
                [
                    'value' => '',
                    'type' => 'string',
                    'group' => 'school',
                    'description' => "Nomor telepon sekolah untuk unit {$unit->code}",
                ]
            );

            Setting::firstOrCreate(
                ['key' => "school_logo_{$code}"],
                [
                    'value' => '',
                    'type' => 'string',
                    'group' => 'school',
                    'description' => "Path logo sekolah untuk unit {$unit->code}",
                ]
            );
        }
    }
}
