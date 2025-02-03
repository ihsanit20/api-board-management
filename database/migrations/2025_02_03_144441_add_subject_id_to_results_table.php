<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // exam_id, student_id, zamat_id থেকে nullable(false) করা
            $table->unsignedBigInteger('exam_id')->nullable(false)->change();
            $table->unsignedBigInteger('student_id')->nullable(false)->change();
            $table->unsignedBigInteger('zamat_id')->nullable(false)->change();

            // নতুন subject_id কলাম যোগ করা
            $table->unsignedBigInteger('subject_id')->after('student_id');

            // ফরেন কী কনস্ট্রেইন্ট যোগ করা
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('restrict');

            // ✅ সব ফিল্ড একসাথে ইউনিক করা (কম্বাইন্ড ইউনিক কনস্ট্রেইন্ট)
            $table->unique(['exam_id', 'student_id', 'zamat_id', 'subject_id'], 'unique_exam_student_zamat_subject');

            // 🔥 পারফরম্যান্স বাড়ানোর জন্য আলাদা আলাদা ইনডেক্স যোগ করা
            $table->index(['exam_id', 'student_id']);
            $table->index(['exam_id', 'zamat_id']);
            $table->index(['exam_id', 'subject_id']);
            $table->index(['exam_id', 'student_id', 'zamat_id', 'subject_id']); // ফুল কম্বাইন্ড ইনডেক্স
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // ইউনিক কনস্ট্রেইন্ট ড্রপ করা
            $table->dropUnique('unique_exam_student_zamat_subject');

            // ইনডেক্স ড্রপ করা
            $table->dropIndex(['exam_id', 'student_id']);
            $table->dropIndex(['exam_id', 'zamat_id']);
            $table->dropIndex(['exam_id', 'subject_id']);
            $table->dropIndex(['exam_id', 'student_id', 'zamat_id', 'subject_id']);

            // ফরেন কী ড্রপ করা
            $table->dropForeign(['subject_id']);

            // subject_id কলাম ড্রপ করা
            $table->dropColumn('subject_id');

            // আগের nullable(true) এ ফিরিয়ে আনা
            $table->unsignedBigInteger('exam_id')->nullable()->change();
            $table->unsignedBigInteger('student_id')->nullable()->change();
            $table->unsignedBigInteger('zamat_id')->nullable()->change();
        });
    }
};
