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
            $table->text('message'); 
            $table->integer('sms_parts')->default(1); 
            $table->integer('sms_count')->default(1); 
            $table->json('numbers');
            $table->decimal('cost', 8, 2)->default(0.00);
            $table->string('event')->index();
            $table->string('status');
            $table->foreignId('institute_id')->nullable()->constrained('institutes');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sms_records');
    }
}