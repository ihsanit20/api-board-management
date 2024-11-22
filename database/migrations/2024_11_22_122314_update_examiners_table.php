<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examiners', function (Blueprint $table) {
            // Check and drop columns if they already exist
            if (Schema::hasColumn('examiners', 'designation')) {
                $table->dropColumn('designation');
            }
            if (Schema::hasColumn('examiners', 'institute')) {
                $table->dropColumn('institute');
            }

            // Add new columns safely
            if (!Schema::hasColumn('examiners', 'nid')) {
                $table->string('nid')->nullable();
            }
            if (!Schema::hasColumn('examiners', 'education')) {
                $table->json('education')->nullable();
            }
            if (!Schema::hasColumn('examiners', 'institute_id')) {
                $table->unsignedBigInteger('institute_id');
            }
            if (!Schema::hasColumn('examiners', 'type')) {
                $table->enum('type', ['examiner', 'guard']);
            }
            if (!Schema::hasColumn('examiners', 'designation')) {
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
            }
            if (!Schema::hasColumn('examiners', 'exam_id')) {
                $table->unsignedBigInteger('exam_id');
            }
            if (!Schema::hasColumn('examiners', 'center_id')) {
                $table->unsignedBigInteger('center_id')->nullable();
            }
            if (!Schema::hasColumn('examiners', 'status')) {
                $table->enum('status', ['active', 'pending', 'rejected'])->default('pending');
            }

            // Add foreign keys
            $table->foreign('institute_id')->references('id')->on('institutes')->onDelete('cascade');
            $table->foreign('center_id')->references('id')->on('centers')->onDelete('set null');
            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('examiners', function (Blueprint $table) {
            // Drop columns added in the migration
            if (Schema::hasColumn('examiners', 'nid')) {
                $table->dropColumn('nid');
            }
            if (Schema::hasColumn('examiners', 'education')) {
                $table->dropColumn('education');
            }
            if (Schema::hasColumn('examiners', 'institute_id')) {
                $table->dropColumn('institute_id');
            }
            if (Schema::hasColumn('examiners', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('examiners', 'designation')) {
                $table->dropColumn('designation');
            }
            if (Schema::hasColumn('examiners', 'exam_id')) {
                $table->dropColumn('exam_id');
            }
            if (Schema::hasColumn('examiners', 'center_id')) {
                $table->dropColumn('center_id');
            }
            if (Schema::hasColumn('examiners', 'status')) {
                $table->dropColumn('status');
            }

            // Drop foreign key constraints
            $table->dropForeign(['institute_id']);
            $table->dropForeign(['center_id']);
            $table->dropForeign(['exam_id']);
        });
    }
};
