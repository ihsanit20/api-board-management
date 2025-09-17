<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('payment_method', ['bkash', 'Bank', 'Cash Payment'])->default('Cash Payment');
            $table->enum('status', ['Pending', 'Completed', 'Failed', 'Cancelled'])->default('Pending');
            $table->string('trx_id', 100)->nullable()->unique();
            $table->string('payer_msisdn', 30)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_payments');
    }
};
