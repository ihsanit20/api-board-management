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
        Schema::create('sms_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity'); // কেনা SMS সংখ্যা
            $table->decimal('price', 8, 2); // মোট দাম
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_stocks');
    }
};
