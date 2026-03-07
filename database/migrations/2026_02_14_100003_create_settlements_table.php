<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->date('payment_date');
            $table->string('payment_method', 20)->default('cash');
            $table->decimal('total_amount', 15, 2);
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
            $table->index('payment_date');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE settlements ADD CONSTRAINT settlements_status_check CHECK (status IN ('completed', 'cancelled'))");
            DB::statement("ALTER TABLE settlements ADD CONSTRAINT settlements_payment_method_check CHECK (payment_method IN ('cash', 'transfer', 'qris'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
