<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')->where('key', 'dangerous_permanent_delete_enabled')->exists();
        if ($exists) {
            return;
        }

        DB::table('settings')->insert([
            'key' => 'dangerous_permanent_delete_enabled',
            'value' => 'false',
            'type' => 'boolean',
            'group' => 'system',
            'description' => 'Izinkan permanent delete superadmin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'dangerous_permanent_delete_enabled')
            ->delete();
    }
};
