<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institute_info', function (Blueprint $table) {
            $table->id();

            // 1:1 relation with institutes (one approved details per institute)
            $table->foreignId('institute_id')
                ->constrained('institutes')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->unique('institute_id'); // ensure one info row per institute

            // Details (no public filters/search needed)
            $table->text('address')->nullable();
            $table->date('established_on')->nullable();
            $table->string('founder_name')->nullable();

            // JSON blobs
            // muhtamim: { "name": "...", "qualification": "...", "ihtemam_years": 0 }
            $table->json('muhtamim')->nullable();

            $table->string('upto_class')->nullable();

            // students: { "maktab": 0, "hifz": 0, "kitab": 0, "total": 0 }
            $table->json('students')->nullable();

            // teachers: { "maktab": 0, "hifz": 0, "kitab": 0, "total": 0 }
            $table->json('teachers')->nullable();

            $table->boolean('has_hostel')->default(false);
            $table->text('land_info')->nullable();
            $table->text('building_summary')->nullable();
            $table->boolean('has_library_for_students')->default(false);

            $table->boolean('has_kutubkhana')->default(false);
            // kutubkhana: { "kitab_count": 0, ... }
            $table->json('kutubkhana')->nullable();

            $table->timestamps();
            // $table->softDeletes(); // দরকার হলে চালু করুন
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_info');
    }
};
