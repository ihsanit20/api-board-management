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
        $group_id = optional($student->group)->id;

        $subjects = $results->map(function ($result) {
            return [
                'id' => $result->examSubject->subject->id,
                'name' => $result->examSubject->subject->name,
                'full_marks' => $result->examSubject->full_marks,
                'pass_marks' => $result->examSubject->pass_marks,
                'mark' => (float) $result->mark,
            ];
        })->sortBy('id')->values();

        $total_mark = $subjects->sum('mark');
        $full_total_marks = $subjects->sum('full_marks');
        $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;
        $grade = $this->calculateGrade($percentage);

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
            'rank' => $rank,
        ];

        return response()->json($response, 200);
    }

    private function calculateRank($exam_id, $zamat_id, $group_id, $student_id, $total_mark)
    {
        $ranks = Result::whereHas('student', function ($query) {
            $query->whereNotNull('roll_number');
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

    public function getInstituteResults($institute_id, $zamat_id)
    {
        $results = Result::whereHas('student', function ($query) use ($institute_id) {
            $query->whereHas('institute', function ($q) use ($institute_id) {
                $q->where('id', $institute_id);
            });
        })
            ->whereHas('zamat', function ($query) use ($zamat_id) {
                $query->where('id', $zamat_id);
            })
            ->with([
                'examSubject.subject',
                'student.group',
                'student.institute'
            ])
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        $subjects = $results->pluck('examSubject.subject')->unique('id')->map(function ($subject) {
            return [
                'id' => $subject->id,
                'name' => $subject->name
            ];
        })->sortBy('id')->values();

        $studentsResults = $results->groupBy('student.id')->map(function ($studentResults) use ($subjects) {
            $student = $studentResults->first()->student;
            $group = optional($student->group);

            $marks = $subjects->map(function ($subject) use ($studentResults) {
                return optional($studentResults->firstWhere('examSubject.subject.id', $subject['id']))->mark ?? 0;
            });

            $total_mark = $marks->sum();
            $full_total_marks = $subjects->count() * 100;
            $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;
            $grade = $this->calculateGrade($percentage);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $total_mark,
                'percentage' => $percentage,
                'grade' => $grade,
                'marks' => $marks->values(),
            ];
        })->values();

        $rankedStudents = $studentsResults->sortByDesc('total_mark')->values()->map(function ($student, $index) {
            $student['rank'] = $index + 1;
            return $student;
        });

        return response()->json([
            'institute' => [
                'id' => $institute_id,
                'name' => optional($results->first()->student->institute)->name,
            ],
            'zamat' => [
                'id' => $zamat_id,
                'name' => optional($results->first()->zamat)->name,
            ],
            'group' => optional($results->first()->student->group)->exists ? [
                'id' => optional($results->first()->student->group)->id,
                'name' => optional($results->first()->student->group)->name,
            ] : null,
            'subjects' => $subjects,
            'students' => $rankedStudents,
        ], 200);
    }
}
