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
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('center_id');
            $table->unsignedBigInteger('zamat_id');
            $table->json('questions');
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('cascade');
            $table->foreign('zamat_id')->references('id')->on('zamats')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('quran_questions');
    }
}