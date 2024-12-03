<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $students = DB::table('students')
            ->whereNotNull('roll_number')
            ->orderBy('roll_number')
            ->get();

        foreach ($students as $student) {
            $previousMaxRollNumber = DB::table('students')
                ->where('exam_id', $student->exam_id)
                ->where('zamat_id', $student->zamat_id)
                ->max('roll_number');
            
            // dd($previousMaxRollNumber);

            $newRollNumber = $previousMaxRollNumber && strlen($previousMaxRollNumber) == 7
                ? $previousMaxRollNumber + 1
                : $student->exam_id . $student->zamat_id . "0001";

            DB::table('students')
                ->where('id', $student->id)
                ->update(['roll_number' => $newRollNumber]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};