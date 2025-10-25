<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('committee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('designation');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['committee_id', 'order']);
            $table->index(['committee_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('committee_members');
    }
};
