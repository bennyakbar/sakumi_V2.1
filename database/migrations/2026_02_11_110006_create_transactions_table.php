<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->date('transaction_date');
            $table->string('type', 20); // income, expense
            $table->foreignId('student_id')->nullable()->constrained('students');
            $table->string('payment_method', 20)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->text('description')->nullable();
            $table->string('receipt_path', 255)->nullable();
            $table->string('proof_path', 255)->nullable();
            $table->string('status', 20)->default('completed');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('transaction_date');
            $table->index('student_id');
            $table->index(['type', 'status']);
        });

        // CHECK constraints
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transaction_type CHECK (type IN ('income', 'expense'))");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_payment_method CHECK (payment_method IN ('cash', 'transfer', 'qris'))");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transaction_status CHECK (status IN ('completed', 'cancelled'))");

            // Immutability trigger — protects 6 fields on completed transactions (null-safe)
            DB::unprepared("
                CREATE OR REPLACE FUNCTION prevent_transaction_update() 
                RETURNS TRIGGER AS \$\$
                BEGIN
                    IF OLD.status = 'completed' AND NEW.status = 'completed' THEN
                        IF OLD.total_amount IS DISTINCT FROM NEW.total_amount
                           OR OLD.transaction_date IS DISTINCT FROM NEW.transaction_date
                           OR OLD.student_id IS DISTINCT FROM NEW.student_id
                           OR OLD.transaction_number IS DISTINCT FROM NEW.transaction_number
                           OR OLD.type IS DISTINCT FROM NEW.type
                           OR OLD.description IS DISTINCT FROM NEW.description THEN
                            RAISE EXCEPTION 'Cannot modify completed transactions';
                        END IF;
                    END IF;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql;

                CREATE TRIGGER check_transaction_immutability
                BEFORE UPDATE ON transactions
                FOR EACH ROW EXECUTE FUNCTION prevent_transaction_update();
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::unprepared('DROP TRIGGER IF EXISTS check_transaction_immutability ON transactions');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_transaction_update()');
        }
        Schema::dropIfExists('transactions');
    }
};
