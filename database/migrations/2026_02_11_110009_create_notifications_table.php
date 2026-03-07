<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students');
            $table->string('type', 50); // payment_success, arrears_reminder
            $table->text('message');
            $table->string('recipient_phone', 20)->nullable();
            $table->string('whatsapp_status', 20)->default('pending');
            $table->timestamp('whatsapp_sent_at')->nullable();
            $table->text('whatsapp_response')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Auto-delete after 6 months

            $table->index('whatsapp_status');
            $table->index('student_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE notifications ADD CONSTRAINT chk_whatsapp_status CHECK (whatsapp_status IN ('pending', 'sent', 'failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
