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
            // Drop the 'amount' and 'late_fee' columns if they exist
            if (Schema::hasColumn('fees', 'amount')) {
                $table->dropColumn('amount');
            }

            if (Schema::hasColumn('fees', 'late_fee')) {
                $table->dropColumn('late_fee');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fees', function (Blueprint $table) {
            // Add back the 'amount' and 'late_fee' columns
            $table->unsignedInteger('amount')->default(0)->after('exam_id');
            $table->unsignedInteger('late_fee')->nullable()->after('amount');
        });
    }
};
