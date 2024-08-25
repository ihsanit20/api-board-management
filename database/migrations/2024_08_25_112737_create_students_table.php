<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id(); // id as roll
            $table->string('registration')->unique();
            $table->foreignId('application_id')->constrained();
            $table->string('name');
            $table->string('name_arabic')->nullable();
            $table->string('father_name');
            $table->string('father_name_arabic')->nullable();
            $table->date('date_of_birth');
            $table->string('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
}
