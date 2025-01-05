<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuranQuestionsTable extends Migration
{
    public function up()
    {
        Schema::create('quran_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id')->nullable();
            $table->unsignedBigInteger('center_id')->nullable();
            $table->unsignedBigInteger('zamat_id')->nullable();
            $table->json('questions')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('restrict');
            $table->foreign('center_id')->references('id')->on('institutes')->onDelete('restrict');
            $table->foreign('zamat_id')->references('id')->on('zamats')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quran_questions');
    }
}
