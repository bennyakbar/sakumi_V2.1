<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string');
            $table->string('group', 50)->default('system'); // school, receipt, notification, arrears, system
            $table->text('description')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE settings ADD CONSTRAINT chk_setting_type CHECK (type IN ('string', 'number', 'boolean', 'json'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
