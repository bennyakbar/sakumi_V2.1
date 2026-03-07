<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->string('period_type', 20)->default('monthly');
            $table->string('period_identifier', 30);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('unpaid');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
            $table->index('invoice_date');
            $table->index(['period_type', 'period_identifier']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('unpaid', 'partially_paid', 'paid', 'cancelled'))");
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_period_type_check CHECK (period_type IN ('monthly', 'annual'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
