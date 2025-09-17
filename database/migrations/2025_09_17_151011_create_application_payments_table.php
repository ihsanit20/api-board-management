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

            // Base FK
            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete();

            // Denormalized FKs for fast reporting/filtering
            $table->foreignId('exam_id')
                ->constrained()             // references id on exams
                ->restrictOnDelete();

            $table->foreignId('institute_id')
                ->constrained()             // references id on institutes
                ->restrictOnDelete();

            $table->foreignId('zamat_id')
                ->constrained('zamats')     // explicit table name
                ->restrictOnDelete();

            // Payment fields
            $table->decimal('amount', 12, 2)->default(0);
            $table->enum('payment_method', ['bkash', 'Bank', 'Cash Payment'])->default('Cash Payment');
            $table->enum('status', ['Pending', 'Completed', 'Failed', 'Cancelled'])->default('Pending');
            $table->string('trx_id', 100)->nullable()->unique();
            $table->string('payer_msisdn', 30)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            // Helpful indexes
            $table->index(['application_id', 'status']);
            $table->index(['exam_id', 'status']);
            $table->index(['institute_id', 'status']);
            $table->index(['zamat_id', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_payments');
    }
};
