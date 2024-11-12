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
        Schema::table('collect_fees', function (Blueprint $table) {
            $table->foreignId('exam_id')->constrained()->after('id');
            $table->foreignId('institute_id')->constrained()->after('exam_id');
            $table->foreignId('zamat_id')->constrained()->after('institute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collect_fees', function (Blueprint $table) {
            $table->dropColumn([
                'exam_id',
                'institute_id',
                'zamat_id'
            ]);
        });
    }
};
