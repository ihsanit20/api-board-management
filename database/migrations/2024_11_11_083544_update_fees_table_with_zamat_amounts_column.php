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
        Schema::table('fees', function (Blueprint $table) {
            // Remove the existing 'zamat_id' column if it exists
            if (Schema::hasColumn('fees', 'zamat_id')) {
                $table->dropColumn('zamat_id');
            }

            // Add the JSON column for 'zamat_amounts'
            if (!Schema::hasColumn('fees', 'zamat_amounts')) {
                $table->json('zamat_amounts')->nullable()->after('exam_id')->comment('Contains zamat_id, amount, and optional late_fee as JSON');
            }

            // Add 'last_date' and 'final_date' columns
            if (!Schema::hasColumn('fees', 'last_date')) {
                $table->date('last_date')->nullable()->after('zamat_amounts')->comment('Last date for normal fee');
            }

            if (!Schema::hasColumn('fees', 'final_date')) {
                $table->date('final_date')->nullable()->after('last_date')->comment('Final date for late fee submission');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            $table->dropColumn('zamat_amounts');
            $table->dropColumn('last_date');
            $table->dropColumn('final_date');
        });
    }
};
