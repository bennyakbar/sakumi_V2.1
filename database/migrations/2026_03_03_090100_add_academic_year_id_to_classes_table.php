<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('academic_year_id')
                ->nullable()
                ->after('unit_id')
                ->constrained('academic_years')
                ->restrictOnDelete();
        });

        $classes = DB::table('classes')
            ->select('id', 'unit_id', 'academic_year')
            ->get();

        foreach ($classes as $class) {
            $code = (string) $class->academic_year;
            if (!preg_match('/^(\d{4})\/(\d{4})$/', $code, $matches)) {
                continue;
            }

            $startYear = (int) $matches[1];
            $endYear = (int) $matches[2];

            DB::table('academic_years')->updateOrInsert(
                ['unit_id' => $class->unit_id, 'code' => $code],
                [
                    'start_date' => sprintf('%d-07-01', $startYear),
                    'end_date' => sprintf('%d-06-30', $endYear),
                    'status' => 'closed',
                    'is_active' => false,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $academicYearId = DB::table('academic_years')
                ->where('unit_id', $class->unit_id)
                ->where('code', $code)
                ->value('id');

            DB::table('classes')
                ->where('id', $class->id)
                ->update(['academic_year_id' => $academicYearId]);
        }

        Schema::table('classes', function (Blueprint $table) {
            $table->index(['unit_id', 'academic_year_id', 'level'], 'classes_unit_ay_level_idx');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropIndex('classes_unit_ay_level_idx');
            $table->dropConstrainedForeignId('academic_year_id');
        });
    }
};
