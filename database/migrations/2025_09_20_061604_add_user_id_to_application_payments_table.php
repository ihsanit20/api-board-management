<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('application_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('application_payments', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('paid_at')
                    ->constrained()       // references users(id)
                    ->nullOnDelete();     // user ডিলিট হলে NULL করে দেবে
            }
        });
    }

    public function down(): void
    {
        Schema::table('application_payments', function (Blueprint $table) {
            if (Schema::hasColumn('application_payments', 'user_id')) {
                // Laravel 8+ helper
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
