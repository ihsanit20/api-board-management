<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParaGroupIdToQuranQuestionsTable extends Migration
{
    public function up()
    {
        Schema::table('quran_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('para_group_id')->nullable()->after('zamat_id'); // nullable para_group_id

            $table->foreign('para_group_id')
                  ->references('id')
                  ->on('para_groups')
                  ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::table('quran_questions', function (Blueprint $table) {
            $table->dropForeign(['para_group_id']);
            $table->dropColumn('para_group_id');
        });
    }
}

