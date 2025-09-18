<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            $t->foreignId('payment_id')
                ->nullable()
                ->constrained('application_payments')
                ->nullOnDelete()
                ->unique(); // 1-to-1 enforce
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $t) {
            // order matters: unique -> fk -> column
            $t->dropUnique(['payment_id']);
            $t->dropConstrainedForeignId('payment_id');
        });
    }
};
