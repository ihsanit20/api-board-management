<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('exam_id')->constrained();
            $table->foreignId('zamat_id')->constrained();
            $table->foreignId('institute_id')->constrained();
            $table->foreignId('group_id')->nullable()->constrained();

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->enum('payment_status', ['Pending', 'Completed', 'Failed'])->default('Pending');
            $table->enum('payment_method', ['Online', 'Offline'])->nullable();

            $table->unsignedInteger('total_amount');
            $table->foreignId('submitted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            
            $table->json('students');

            $table->timestamps();

            $table->index(['zamat_id', 'institute_id']);  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('applications');
    }
}
