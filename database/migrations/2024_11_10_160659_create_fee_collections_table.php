<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fee_collections', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('exam_id')->constrained();
            $table->foreignId('institute_id')->constrained();
            $table->foreignId('zamat_id')->constrained();

            $table->enum('payment_method', ['online', 'offline'])->default('offline');
            $table->string('transaction_id')->nullable();
            $table->json('student_ids');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_collections');
    }
};
