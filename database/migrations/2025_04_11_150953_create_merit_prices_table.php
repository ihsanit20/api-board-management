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
        Schema::create('merit_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->onDelete('cascade');

            $table->foreignId('zamat_id')
                ->constrained('zamats')
                ->onDelete('cascade');

            $table->string('merit_name');
            $table->unsignedInteger('price_amount'); // দশমিক ছাড়া মান

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merit_prices');
    }
};
