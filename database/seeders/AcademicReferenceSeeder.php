<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicReferenceSeeder extends Seeder
{
    public function run(): void
    {

        $year = '2025/2026';

        /*
        |--------------------------------------------------------------------------
        | CLASSES
        |--------------------------------------------------------------------------
        */

        $units = DB::table('units')->pluck('id');

        $levels = [
            ['name' => '1A', 'level' => 1],
            ['name' => '2A', 'level' => 2],
            ['name' => '3A', 'level' => 3],
            ['name' => '4A', 'level' => 4],
            ['name' => '5A', 'level' => 5],
            ['name' => '6A', 'level' => 6],
        ];

        foreach ($units as $unitId) {

            foreach ($levels as $class) {

                DB::table('classes')->updateOrInsert(
                    [
                        'unit_id' => $unitId,
                        'name' => $class['name'],
                        'academic_year' => $year
                    ],
                    [
                        'level' => $class['level'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | STUDENT CATEGORIES
        |--------------------------------------------------------------------------
        */

        $categories = [
            [
                'code' => 'REG',
                'name' => 'Reguler',
                'description' => 'Siswa reguler',
                'discount_percentage' => 0
            ],
            [
                'code' => 'SUB',
                'name' => 'Subsidi',
                'description' => 'Siswa subsidi',
                'discount_percentage' => 25
            ],
        ];

        foreach ($units as $unitId) {

            foreach ($categories as $cat) {

                DB::table('student_categories')->updateOrInsert(
                    [
                        'unit_id' => $unitId,
                        'code' => $cat['code']
                    ],
                    [
                        'name' => $cat['name'],
                        'description' => $cat['description'],
                        'discount_percentage' => $cat['discount_percentage'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }

    }
}