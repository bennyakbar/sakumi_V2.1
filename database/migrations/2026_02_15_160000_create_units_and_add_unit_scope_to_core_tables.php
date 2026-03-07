<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that require unit_id scoping.
     *
     * Order matters: parent tables first so FK constraints resolve.
     */
    private array $tables = [
        'users',
        'classes',
        'student_categories',
        'fee_types',
        'fee_matrix',
        'students',
        'accounts',
        'categories',
        'transactions',
        'student_obligations',
        'invoices',
        'settlements',
        'notifications',
    ];

    public function up(): void
    {
        // 1. Create units table
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Seed units with deterministic IDs
        DB::table('units')->insert([
            ['id' => 1, 'code' => 'MI', 'name' => 'Madrasah Ibtidaiyah (MI)', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'RA', 'name' => 'Raudhatul Athfal (RA)', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'code' => 'DTA', 'name' => 'Diniyah Takmiliyah Awaliyah (DTA)', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. Add unit_id: nullable → backfill MI → NOT NULL + FK (restrictOnDelete) + index
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'unit_id')) {
                continue;
            }

            // Add nullable column first
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('unit_id')->nullable()->after('id');
            });

            // Backfill all existing rows to MI (id=1)
            DB::table($table)->whereNull('unit_id')->update(['unit_id' => 1]);

            // Make NOT NULL, add FK with restrict on delete, add index
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('unit_id')->nullable(false)->change();
                $blueprint->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
                $blueprint->index('unit_id');
            });
        }

        // 4. Update unique constraints to be compound with unit_id
        if (DB::getDriverName() !== 'sqlite') {
            // students: nis and nisn unique per unit
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_nis_unique');
            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_nisn_unique');
            Schema::table('students', function (Blueprint $table) {
                $table->unique(['unit_id', 'nis'], 'students_unit_nis_unique');
                $table->unique(['unit_id', 'nisn'], 'students_unit_nisn_unique');
            });

            // fee_types: code unique per unit
            DB::statement('ALTER TABLE fee_types DROP CONSTRAINT IF EXISTS fee_types_code_unique');
            Schema::table('fee_types', function (Blueprint $table) {
                $table->unique(['unit_id', 'code'], 'fee_types_unit_code_unique');
            });

            // student_categories: code unique per unit
            DB::statement('ALTER TABLE student_categories DROP CONSTRAINT IF EXISTS student_categories_code_unique');
            Schema::table('student_categories', function (Blueprint $table) {
                $table->unique(['unit_id', 'code'], 'student_categories_unit_code_unique');
            });

            // accounts: code unique per unit
            DB::statement('ALTER TABLE accounts DROP CONSTRAINT IF EXISTS accounts_code_unique');
            Schema::table('accounts', function (Blueprint $table) {
                $table->unique(['unit_id', 'code'], 'accounts_unit_code_unique');
            });

            // categories: code unique per unit
            DB::statement('ALTER TABLE categories DROP CONSTRAINT IF EXISTS categories_code_unique');
            Schema::table('categories', function (Blueprint $table) {
                $table->unique(['unit_id', 'code'], 'categories_unit_code_unique');
            });

            // classes: (name, academic_year) unique per unit
            DB::statement('ALTER TABLE classes DROP CONSTRAINT IF EXISTS classes_name_academic_year_unique');
            Schema::table('classes', function (Blueprint $table) {
                $table->unique(['unit_id', 'name', 'academic_year'], 'classes_unit_name_ay_unique');
            });
        } else {
            // SQLite: replace legacy unique indexes with unit-scoped unique indexes.
            DB::statement('DROP INDEX IF EXISTS students_nis_unique');
            DB::statement('DROP INDEX IF EXISTS students_nisn_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS students_unit_nis_unique ON students (unit_id, nis)');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS students_unit_nisn_unique ON students (unit_id, nisn)');

            DB::statement('DROP INDEX IF EXISTS fee_types_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS fee_types_unit_code_unique ON fee_types (unit_id, code)');

            DB::statement('DROP INDEX IF EXISTS student_categories_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS student_categories_unit_code_unique ON student_categories (unit_id, code)');

            DB::statement('DROP INDEX IF EXISTS accounts_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS accounts_unit_code_unique ON accounts (unit_id, code)');

            DB::statement('DROP INDEX IF EXISTS categories_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS categories_unit_code_unique ON categories (unit_id, code)');

            DB::statement('DROP INDEX IF EXISTS classes_name_academic_year_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS classes_unit_name_ay_unique ON classes (unit_id, name, academic_year)');
        }
    }

    public function down(): void
    {
        // 1. Reverse compound unique constraints
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('students', function (Blueprint $table) {
                $table->dropUnique('students_unit_nis_unique');
                $table->dropUnique('students_unit_nisn_unique');
            });
            // Re-add original uniques only if column still exists
            if (Schema::hasColumn('students', 'nis')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->unique('nis');
                    $table->unique('nisn');
                });
            }

            Schema::table('fee_types', function (Blueprint $table) {
                $table->dropUnique('fee_types_unit_code_unique');
            });
            if (Schema::hasColumn('fee_types', 'code')) {
                Schema::table('fee_types', function (Blueprint $table) {
                    $table->unique('code');
                });
            }

            Schema::table('student_categories', function (Blueprint $table) {
                $table->dropUnique('student_categories_unit_code_unique');
            });
            if (Schema::hasColumn('student_categories', 'code')) {
                Schema::table('student_categories', function (Blueprint $table) {
                    $table->unique('code');
                });
            }

            Schema::table('accounts', function (Blueprint $table) {
                $table->dropUnique('accounts_unit_code_unique');
            });
            if (Schema::hasColumn('accounts', 'code')) {
                Schema::table('accounts', function (Blueprint $table) {
                    $table->unique('code');
                });
            }

            Schema::table('categories', function (Blueprint $table) {
                $table->dropUnique('categories_unit_code_unique');
            });
            if (Schema::hasColumn('categories', 'code')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->unique('code');
                });
            }

            Schema::table('classes', function (Blueprint $table) {
                $table->dropUnique('classes_unit_name_ay_unique');
            });
            if (Schema::hasColumn('classes', 'name')) {
                Schema::table('classes', function (Blueprint $table) {
                    $table->unique(['name', 'academic_year']);
                });
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS students_unit_nis_unique');
            DB::statement('DROP INDEX IF EXISTS students_unit_nisn_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS students_nis_unique ON students (nis)');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS students_nisn_unique ON students (nisn)');

            DB::statement('DROP INDEX IF EXISTS fee_types_unit_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS fee_types_code_unique ON fee_types (code)');

            DB::statement('DROP INDEX IF EXISTS student_categories_unit_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS student_categories_code_unique ON student_categories (code)');

            DB::statement('DROP INDEX IF EXISTS accounts_unit_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS accounts_code_unique ON accounts (code)');

            DB::statement('DROP INDEX IF EXISTS categories_unit_code_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS categories_code_unique ON categories (code)');

            DB::statement('DROP INDEX IF EXISTS classes_unit_name_ay_unique');
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS classes_name_academic_year_unique ON classes (name, academic_year)');
        }

        // 2. Drop unit_id from all tables (reverse order for FK safety)
        $reversed = array_reverse($this->tables);
        foreach ($reversed as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'unit_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['unit_id']);
                $blueprint->dropIndex(['unit_id']);
                $blueprint->dropColumn('unit_id');
            });
        }

        // 3. Drop units table
        Schema::dropIfExists('units');
    }
};
