<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institute_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institute_id')
                ->nullable()
                ->constrained('institutes')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->string('name')->nullable()->index();
            $table->string('phone')->nullable()->index();

            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // আবেদন টাইপ: নতুন / আপডেট / শুধুই ডিটেইলস
            $table->enum('type', ['NEW', 'UPDATE', 'DETAILS_ONLY'])->index();

            // পাবলিক ফর্মের পুরো ডেটা (muhtamim, students, teachers, hostel, land, building, kutubkhana, ইত্যাদি)
            $table->json('payload_json');

            // রিভিউ ওয়ার্কফ্লো
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_info'])
                ->default('pending')
                ->index();

            // কে কবে রিভিউ করলো
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();

            // (ঐচ্ছিক) ভবিষ্যতের জন্য জায়গা রাখতে চাইলে:
            // $table->json('approved_snapshot_json')->nullable();
            // $table->json('merge_map')->nullable(); // কোন ফিল্ড অনুমোদিত হলো

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institute_applications');
    }
};
