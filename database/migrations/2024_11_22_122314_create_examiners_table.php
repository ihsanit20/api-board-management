<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examiners', function (Blueprint $table) {
            $table->id();
            $table->string('examiner_code')->unique()->nullable();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('nid')->nullable();
            $table->string('address')->nullable();
            $table->json('education')->nullable();
            $table->json('experience')->nullable();
            $table->string('ex_experience')->nullable();
            $table->unsignedBigInteger('institute_id');
            $table->string('student_count')->nullable();
            $table->enum('type', ['examiner', 'guard']);
            $table->enum('designation', [
                'হল পরিদর্শক',
                'নেগরানে আলা',
                'সহকারী নেগরান',
                'হল ব্যবস্থাপক',
                'মুমতাহিন (খাতা)',
                'মুমতাহিন (মৌখিক, মীযান বালক)',
                'মুমতাহিনা (মৌখিক, মীযান বালিকা)',
                'মুমতাহিন (নাযিরা)',
                'মুমতাহিন (হিফয)'
            ])->nullable();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('center_id')->nullable();
            $table->enum('status', ['active', 'pending', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('set null');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examiners');
    }
};