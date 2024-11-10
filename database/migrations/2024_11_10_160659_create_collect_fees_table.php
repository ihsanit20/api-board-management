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
        Schema::create('collect_fees', function (Blueprint $table) {
            $table->id();
            $table->json('student_ids');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['online', 'offline'])->default('offline');
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collect_fees');
    }
};
