<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSmsPartsToSmsLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->integer('sms_parts')->default(1)->after('message'); // নতুন sms_parts কলাম তৈরি করা হচ্ছে
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn('sms_parts');
        });
    }
}
