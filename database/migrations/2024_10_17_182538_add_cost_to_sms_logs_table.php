<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->decimal('cost', 8, 2)->default(0); // Add cost column with decimal type
        });
    }
    
    public function down()
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
    
};
