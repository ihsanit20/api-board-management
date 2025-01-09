<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParaGroupsTable extends Migration
{
    public function up()
    {
        Schema::create('para_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('zamat_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('zamat_id')->references('id')->on('zamats')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('para_groups');
    }
}
