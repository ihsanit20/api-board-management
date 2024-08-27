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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->onDelete('restrict');
            $table->foreignId('exam_id')->constrained()->onDelete('restrict');
            $table->foreignId('institute_id')->constrained()->onDelete('restrict');
            $table->foreignId('zamat_id')->constrained()->onDelete('restrict');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('center_id')->nullable()->constrained('institutes')->onDelete('set null');
            $table->string('name');
            $table->string('name_arabic')->nullable();
            $table->string('father_name');
            $table->string('father_name_arabic')->nullable();
            $table->date('date_of_birth');
            $table->string('address')->nullable();
            $table->string('gender');
            $table->string('registration_number', 8)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
