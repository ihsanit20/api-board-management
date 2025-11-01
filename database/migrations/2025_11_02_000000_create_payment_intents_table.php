<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();

            // Online intent identification
            $table->string('token', 64)->unique();

            // Target combo
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('zamat_id')->constrained('zamats')->cascadeOnUpdate()->restrictOnDelete();

            // What we expect to receive
            $table->decimal('expected_amount', 12, 2);

            // Selected candidates (registration numbers from applications JSON)
            $table->json('registrations');

            // Lifecycle
            $table->enum('status', ['initiated', 'completed', 'expired', 'failed', 'canceled'])
                ->default('initiated');

            // Gateway fields (filled after execute)
            $table->string('transaction_id', 64)->nullable(); // e.g., bkash trxID
            $table->json('meta')->nullable();                 // raw gateway payload or extras

            // Optional: when we consider this intent no longer valid
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['exam_id', 'zamat_id', 'institute_id'], 'pi_combo_idx');
            $table->index(['status', 'expires_at'], 'pi_status_exp_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
