<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('student_categories', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('fee_types', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('fee_matrix', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('fee_matrix', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('fee_types', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('student_categories', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('classes', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
