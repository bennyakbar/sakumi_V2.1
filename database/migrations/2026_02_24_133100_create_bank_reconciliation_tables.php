<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->string('bank_account_name', 150);
            $table->smallInteger('period_year');
            $table->tinyInteger('period_month');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'bank_account_name', 'period_year', 'period_month'], 'uq_bank_recon_session');
            $table->index(['unit_id', 'period_year', 'period_month', 'status'], 'idx_bank_recon_session_period');
        });

        Schema::create('bank_reconciliation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_session_id')->constrained('bank_reconciliation_sessions')->cascadeOnDelete();
            $table->date('line_date');
            $table->string('description', 255)->nullable();
            $table->string('reference', 120)->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('type', 20)->default('debit');
            $table->string('match_status', 20)->default('unmatched');
            $table->foreignId('matched_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->index(['bank_reconciliation_session_id', 'match_status'], 'idx_bank_recon_lines_status');
            $table->index(['line_date', 'amount'], 'idx_bank_recon_lines_lookup');
        });

        Schema::create('bank_reconciliation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_session_id')->constrained('bank_reconciliation_sessions')->cascadeOnDelete();
            $table->string('action', 60);
            $table->json('payload')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['bank_reconciliation_session_id', 'created_at'], 'idx_bank_recon_logs_session_time');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE bank_reconciliation_sessions ADD CONSTRAINT chk_bank_recon_session_status CHECK (status IN ('draft', 'in_review', 'closed'))");
            DB::statement('ALTER TABLE bank_reconciliation_sessions ADD CONSTRAINT chk_bank_recon_session_month CHECK (period_month BETWEEN 1 AND 12)');
            DB::statement("ALTER TABLE bank_reconciliation_lines ADD CONSTRAINT chk_bank_recon_line_type CHECK (type IN ('debit', 'credit'))");
            DB::statement("ALTER TABLE bank_reconciliation_lines ADD CONSTRAINT chk_bank_recon_line_match_status CHECK (match_status IN ('matched', 'unmatched', 'adjusted'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_logs');
        Schema::dropIfExists('bank_reconciliation_lines');
        Schema::dropIfExists('bank_reconciliation_sessions');
    }
};
