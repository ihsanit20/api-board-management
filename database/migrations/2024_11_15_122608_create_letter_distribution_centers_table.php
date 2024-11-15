<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLetterDistributionCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('letter_distribution_centers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('area_id')->constrained();
            $table->foreignId('institute_id')->constrained();
            $table->string('name')->unique();
            $table->json('institute_ids')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['area_id', 'institute_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('letter_distribution_centers');
    }
}
