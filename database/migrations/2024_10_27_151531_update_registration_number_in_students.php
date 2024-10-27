<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all students and store existing registration numbers in an array
        $students = DB::table('students')->get();
        $existingRegistrationNumbers = $students->pluck('registration_number')->toArray();

        foreach ($students as $student) {
            $newRegistrationNumber = '';

            // Ensure the generated registration number is unique
            do {
                $newRegistrationNumber = '22' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            } while (in_array($newRegistrationNumber, $existingRegistrationNumbers));

            // Store the new registration number in the array to ensure it stays unique
            $existingRegistrationNumbers[] = $newRegistrationNumber;

            // Update the student registration number
            DB::table('students')
                ->where('id', $student->id)
                ->update(['registration_number' => $newRegistrationNumber]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No reversal logic, as the original registration numbers will be lost
    }
};
