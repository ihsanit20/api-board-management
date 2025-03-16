<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Result;
use App\Models\Student;

class ResultSummaryController extends Controller
{
    public function summaryPrint($exam_id)
    {
        $exam = Exam::find($exam_id);

        if (!$exam) {
            return response()->json(['message' => 'Exam not found'], 404);
        }

        $studentsWithRoll = Student::whereNotNull('roll_number')->count();

        $results = Result::where('exam_id', $exam_id)
            ->with(['examSubject'])
            ->get()
            ->groupBy('student_id');

        $counts = [
            'total_pass' => 0,
            'total_fail' => 0,
            'star_mark' => 0,
            'first_division' => 0,
            'second_division' => 0,
            'third_division' => 0
        ];

        $total_present = 0;

        foreach ($results as $studentResults) {
            $hasNullMark = $studentResults->contains(fn($result) => $result->mark === null);

            if ($hasNullMark) {
                continue;
            }

            $total_present++;

            $total_mark = $studentResults->sum('mark');
            $full_total_marks = $studentResults->sum(fn($result) => $result->examSubject->full_marks);

            if ($full_total_marks == 0) continue;

            $percentage = ($total_mark / $full_total_marks) * 100;

            if ($percentage >= 80) {
                $counts['star_mark']++;
                $counts['total_pass']++;
            } elseif ($percentage >= 65) {
                $counts['first_division']++;
                $counts['total_pass']++;
            } elseif ($percentage >= 50) {
                $counts['second_division']++;
                $counts['total_pass']++;
            } elseif ($percentage >= 35) {
                $counts['third_division']++;
                $counts['total_pass']++;
            } else {
                $counts['total_fail']++;
            }
        }

        $total_absent = $studentsWithRoll - $total_present;

        $response = [
            'exam_name' => $exam->name,
            'total_students' => $studentsWithRoll,
            'total_present' => $total_present,
            'total_absent' => $total_absent,
            'total_pass' => $counts['total_pass'],
            'total_fail' => $counts['total_fail'],
            'star_mark' => $counts['star_mark'],
            'first_division' => $counts['first_division'],
            'second_division' => $counts['second_division'],
            'third_division' => $counts['third_division'],
            'percentages' => [
                'present_percentage' => round(($total_present / $studentsWithRoll) * 100, 2),
                'absent_percentage' => round(($total_absent / $studentsWithRoll) * 100, 2),
                'pass_percentage' => round(($counts['total_pass'] / $total_present) * 100, 2),
                'fail_percentage' => round(($counts['total_fail'] / $total_present) * 100, 2),
                'star_mark_percentage' => round(($counts['star_mark'] / $total_present) * 100, 2),
                'first_division_percentage' => round(($counts['first_division'] / $total_present) * 100, 2),
                'second_division_percentage' => round(($counts['second_division'] / $total_present) * 100, 2),
                'third_division_percentage' => round(($counts['third_division'] / $total_present) * 100, 2),
            ],
        ];

        return response()->json($response, 200);
    }
}