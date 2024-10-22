<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // আগে 'institute' রোলগুলো 'Operator' এ আপডেট করা হচ্ছে
            DB::table('users')
                ->where('role', 'institute')
                ->update(['role' => 'Operator']);
            
            // এরপর enum কলাম আপডেট হচ্ছে নতুন রোলের সাথে
            $table->enum('role', ['Operator', 'Admin', 'Super Admin', 'Developer'])
                  ->default('Operator')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverse enum change to original
            $table->string('role')->default('institute')->change();
        });
    }
};
