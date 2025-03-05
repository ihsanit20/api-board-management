<?php

namespace App\Http\Controllers;

use App\Models\Result;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function getStudentResult($roll_number)
    {
        $results = Result::whereHas('student', function ($query) use ($roll_number) {
            $query->where('roll_number', $roll_number);
        })
            ->with([
                'exam',
                'examSubject.subject',
                'zamat.department',
                'student.group',
                'student.institute'
            ])
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        $student = $results->first()->student;
        $exam = $results->first()->exam;
        $zamat = $results->first()->zamat;
        $group_id = optional($student->group)->id; // ✅ গ্রুপ থাকলে, তা সংরক্ষণ করছি

        $subjects = $results->map(function ($result) {
            return [
                'id' => $result->examSubject->subject->id,
                'name' => $result->examSubject->subject->name,
                'full_marks' => $result->examSubject->full_marks,
                'pass_marks' => $result->examSubject->pass_marks,
                'mark' => (float) $result->mark,
            ];
        });

        $total_mark = $subjects->sum('mark');
        $full_total_marks = $subjects->sum('full_marks');
        $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;
        $grade = $this->calculateGrade($percentage);

        // ✅ মেধাক্রম বের করা (গ্রুপসহ)
        $rank = $this->calculateRank($exam->id, $zamat->id, $group_id, $student->id, $total_mark);

        $response = [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'registration_number' => $student->registration_number,
                'group' => optional($student->group)->name,
                'institute' => optional($student->institute)->name,
                'institute_code' => optional($student->institute)->institute_code,
            ],
            'exam' => [
                'id' => $exam->id,
                'name' => $exam->name,
            ],
            'zamat' => [
                'id' => $zamat->id,
                'name' => $zamat->name,
                'department' => optional($zamat->department)->name,
            ],
            'subjects' => $subjects,
            'full_total_marks' => $full_total_marks,
            'total_mark' => $total_mark,
            'percentage' => $percentage,
            'grade' => $grade,
            'rank' => $rank, // ✅ মেধাক্রম (ক, খ, গ সহ)
        ];

        return response()->json($response, 200);
    }

    /**
     * ✅ মেধাক্রম নির্ধারণ করার ফাংশন (গ্রুপ সহ এবং একই নাম্বার থাকলে ক, খ, গ যুক্ত)
     */
    private function calculateRank($exam_id, $zamat_id, $group_id, $student_id, $total_mark)
    {
        $ranks = Result::whereHas('student', function ($query) {
            $query->whereNotNull('roll_number'); // ✅ যাদের রোল নাম্বার আছে শুধু তাদের খুঁজবে
        })
            ->whereHas('zamat', function ($query) use ($zamat_id) {
                $query->where('id', $zamat_id);
            })
            ->whereHas('exam', function ($query) use ($exam_id) {
                $query->where('id', $exam_id);
            })
            ->when($group_id, function ($query) use ($group_id) { // ✅ গ্রুপ ফিল্টার (যদি গ্রুপ থাকে)
                return $query->whereHas('student.group', function ($q) use ($group_id) {
                    $q->where('id', $group_id);
                });
            })
            ->selectRaw('student_id, SUM(mark) as total_mark')
            ->groupBy('student_id')
            ->orderByDesc('total_mark')
            ->get();

        // ✅ একই নাম্বার থাকলে (ক, খ, গ) যুক্ত করা
        $rank = 1;
        $previous_mark = null;
        $suffix_index = 0;
        $suffixes = ['ক', 'খ', 'গ', 'ঘ', 'ঙ', 'চ', 'ছ', 'জ', 'ঝ']; // ✅ একই মার্ক থাকলে এভাবে দেখাবে: 1(ক), 1(খ) ইত্যাদি

        foreach ($ranks as $key => $rankedStudent) {
            if ($rankedStudent->total_mark !== $previous_mark) {
                $rank_number = $rank; // নতুন নাম্বার এলে র‍্যাঙ্ক আপডেট হবে
                $suffix_index = 0; // সাফিক্স রিসেট হবে
            } else {
                $rank_number = $rank . " (" . $suffixes[$suffix_index] . ")"; // একই মার্ক হলে (ক, খ, গ) যোগ হবে
                $suffix_index++;
            }

            if ($rankedStudent->student_id == $student_id) {
                return $rank_number;
            }

            $previous_mark = $rankedStudent->total_mark;
            $rank++;
        }

        return null; // যদি কোনো র‌্যাঙ্ক খুঁজে না পাওয়া যায়
    }

    /**
     * ✅ গ্রেড নির্ধারণ করার ফাংশন
     */
    private function calculateGrade($percentage)
    {
        if ($percentage >= 80) {
            return 'মুমতায';
        } elseif ($percentage >= 65) {
            return 'যায়্যিদ জিদ্দান';
        } elseif ($percentage >= 50) {
            return 'যায়্যিদ';
        } elseif ($percentage >= 35) {
            return 'মাকবুল';
        } else {
            return 'রাসিব';
        }
    }
}