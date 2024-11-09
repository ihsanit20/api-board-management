<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSmsRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('sms_records', function (Blueprint $table) {
            $table->id();
            $table->text('message'); // মেসেজ সংরক্ষণ
            $table->json('receiver_info'); // JSON ফিল্ডে রিসিভার ইনফো
            $table->integer('receiver_count')->default(0); // মোট রিসিভার সংখ্যা
            $table->integer('sms_parts')->default(1); // SMS অংশ সংখ্যা
            $table->decimal('total_cost', 8, 2)->default(0.00); // মোট খরচ
            $table->timestamps(); // created_at এবং updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_records');
    }
}
