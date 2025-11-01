<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();

            // Target combo
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('institute_id')->constrained('institutes')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('zamat_id')->constrained('zamats')->cascadeOnUpdate()->restrictOnDelete();

            // Payment channel/method/status
            $table->enum('channel', ['online', 'offline']);
            $table->enum('method', ['bkash', 'nagad', 'card', 'cash', 'bank', 'other'])->default('bkash');
            $table->enum('status', ['Completed', 'Refunded', 'Chargeback'])->default('Completed');

            // Amounts
            $table->decimal('gross_amount', 12, 2);      // অন্তত যা কাটাপড়া/চার্জসহ প্রদেয়
            $table->decimal('net_amount', 12, 2)->nullable(); // সেটেল্ড/নেট (ইচ্ছাধীন)
            $table->decimal('service_charge', 12, 2)->default(0);

            // Headcount snapshot at payment time
            $table->unsignedInteger('students_count')->default(0);

            // Gateway references (trx_id unique; null allowed for offline)
            $table->string('trx_id', 64)->nullable()->unique();
            $table->string('payment_id', 64)->nullable();   // gateway paymentID
            $table->string('payer_msisdn', 30)->nullable();
            $table->timestamp('paid_at')->nullable();

            // Optional operator (offline, or who forced/approved)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->json('meta')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['exam_id', 'zamat_id', 'institute_id'], 'fp_combo_idx');
            $table->index(['status', 'paid_at'], 'fp_status_paid_idx');
            $table->index('method', 'fp_method_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
