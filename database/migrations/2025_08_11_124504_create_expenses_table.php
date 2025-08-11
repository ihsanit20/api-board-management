<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();

            $table->string('purpose');                 // বাবদ (required)
            $table->decimal('amount', 14, 2);         // এমাউন্ট (required)

            // মাধ্যম(ব্যক্তি): users.id (nullable)
            $table->unsignedBigInteger('medium_user_id')->nullable();
            $table->foreign('medium_user_id')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->text('description')->nullable();  // ডেসক্রিপশন (nullable)
            $table->date('date')->nullable();         // তারিখ (nullable)
            $table->string('voucher_no')->nullable(); // ভাউচার নং (nullable)

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('expenses');
    }
}