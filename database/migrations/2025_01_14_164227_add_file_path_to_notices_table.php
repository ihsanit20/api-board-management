<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('file_path')->nullable()->after('publish_date');
        });
    }

    public function down()
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }

};
