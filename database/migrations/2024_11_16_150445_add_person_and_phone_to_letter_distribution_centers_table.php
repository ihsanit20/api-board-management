<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonAndPhoneToLetterDistributionCentersTable extends Migration
{

    public function up()
    {
        Schema::table('letter_distribution_centers', function (Blueprint $table) {
            $table->string('person')->nullable()->after('institute_ids');
            $table->string('phone')->nullable()->after('person');
        });
    }


    public function down()
    {
        Schema::table('letter_distribution_centers', function (Blueprint $table) {
            $table->dropColumn(['person', 'phone']);
        });
    }
}
