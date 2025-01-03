<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExamSubjectsTable extends Migration
{
    public function up()
    {
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('restrict');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('restrict');
            $table->integer('full_marks');
            $table->integer('pass_marks');
            $table->timestamps();

            $table->unique(['exam_id', 'subject_id'], 'unique_exam_subject');
        });
    }

    public function down()
    {
        Schema::dropIfExists('exam_subjects');
    }
}
