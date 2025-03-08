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

    private function convertToBanglaNumber($number)
    {
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        return str_replace($englishDigits, $banglaDigits, $number);
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
            ->having('total_mark', '>', 0)
            ->orderByDesc('total_mark')
            ->get();

        $rank = 1;
        $previous_mark = null;
        $suffix_index = 0;
        $suffixes = ['ক', 'খ', 'গ', 'ঘ', 'ঙ', 'চ', 'ছ', 'জ', 'ঝ']; // ✅ একই মার্ক থাকলে এভাবে দেখাবে: 1(ক), 1(খ) ইত্যাদি

        foreach ($ranks as $key => $rankedStudent) {
            if ($rankedStudent->total_mark !== $previous_mark) {
                $rank_number = $this->convertToBanglaNumber($rank); // বাংলা সংখ্যায় রূপান্তর
                $suffix_index = 0; // সাফিক্স রিসেট হবে
            } else {
                $rank_number = $this->convertToBanglaNumber($rank) . " (" . $suffixes[$suffix_index] . ")"; // বাংলা সংখ্যায় রূপান্তর
                $suffix_index++;
            }

            if ($rankedStudent->student_id == $student_id) {
                return $rank_number;
            }

            $previous_mark = $rankedStudent->total_mark;
            $rank++;
        }

        return null;
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

    public function getInstituteResults($institute_id, $zamat_id, $exam_id)
    {
        $results = Result::whereHas('student', function ($query) use ($institute_id) {
            $query->whereHas('institute', function ($q) use ($institute_id) {
                $q->where('id', $institute_id);
            });
        })
            ->whereHas('zamat', function ($query) use ($zamat_id) {
                $query->where('id', $zamat_id);
            })
            ->where('exam_id', $exam_id) // exam_id দিয়ে ভ্যালিডেশন
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

        $group = null;
        $firstStudent = $results->first()->student;
        if ($firstStudent && $firstStudent->group) {
            $group = [
                'id' => $firstStudent->group->id,
                'name' => $firstStudent->group->name,
            ];
        }

        $studentsResults = $results->groupBy('student.id')->map(function ($studentResults) use ($subjects, $zamat_id) {
            $student = $studentResults->first()->student;

            $marks = $subjects->map(function ($subject) use ($studentResults) {
                return optional($studentResults->firstWhere('examSubject.subject.id', $subject['id']))->mark ?? 0;
            });

            $total_mark = $marks->sum();

            $full_marks = $studentResults->map(function ($result) {
                return (float) ($result->examSubject->full_marks ?? 0);
            });

            $full_total_marks = $full_marks->sum();

            $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;
            $grade = $this->calculateGrade($percentage);
            $rank = $this->calculateRank($studentResults->first()->exam->id, $zamat_id, optional($student->group)->id ?? null, $student->id, $total_mark);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $total_mark,
                'percentage' => $percentage,
                'grade' => $grade,
                'marks' => $marks->values(),
                'rank' => $rank,
            ];
        })->values();

        $studentsResults = $studentsResults->sortBy('roll_number')->values();

        // রেসপন্স তৈরি করা
        $response = [
            'institute' => [
                'id' => $institute_id,
                'name' => optional($results->first()->student->institute)->name,
            ],
            'zamat' => [
                'id' => $zamat_id,
                'name' => optional($results->first()->zamat)->name,
                'department' => optional($results->first()->zamat->department)->name,
            ],
            'exam' => [
                'id' => $exam_id,
                'name' => optional($results->first()->exam)->name, // exam এর নাম যোগ করা হয়েছে
            ],
            'subjects' => $subjects,
            'students' => $studentsResults,
        ];

        // যদি গ্রুপ থাকে তাহলে গ্রুপের তথ্য যোগ করা
        if ($group) {
            $response['group'] = $group;
        }

        return response()->json($response, 200);
    }

    public function getMeritList(Request $request, $exam_id, $zamat_id, $group_id = null)
    {
        $limit = $request->input('limit', 10); // ডিফল্ট সর্বোচ্চ ১০ জন শিক্ষার্থী দেখাবে

        $query = Result::whereHas('zamat', function ($query) use ($zamat_id) {
            $query->where('id', $zamat_id);
        })
            ->whereHas('exam', function ($query) use ($exam_id) {
                $query->where('id', $exam_id);
            })
            ->when($group_id, function ($query) use ($group_id) {
                return $query->whereHas('student.group', function ($q) use ($group_id) {
                    $q->where('id', $group_id);
                });
            })
            ->with([
                'student.group',
                'student.institute'
            ])
            ->selectRaw('student_id, SUM(mark) as total_mark')
            ->groupBy('student_id')
            ->having('total_mark', '>', 0)
            ->orderByDesc('total_mark')
            ->limit($limit)
            ->get();

        if ($query->isEmpty()) {
            return response()->json(['message' => 'No merit list found'], 404);
        }

        // ✅ র‌্যাংক গণনা
        $rankedStudents = [];
        $rank = 1;
        $previous_mark = null;
        $suffix_index = 0;
        $suffixes = ['ক', 'খ', 'গ', 'ঘ', 'ঙ', 'চ', 'ছ', 'জ', 'ঝ'];

        foreach ($query as $key => $result) {
            $student = $result->student;
            $group = optional($student->group);

            // ✅ একই নম্বর থাকলে (ক, খ, গ) যুক্ত করা
            if ($result->total_mark !== $previous_mark) {
                $rank_number = $this->convertToBanglaNumber($rank);
                $suffix_index = 0;
            } else {
                $rank_number = $this->convertToBanglaNumber($rank) . " (" . $suffixes[$suffix_index] . ")";
                $suffix_index++;
            }

            $rankedStudents[] = [
                'rank' => $rank_number,
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $result->total_mark,
                'group' => optional($student->group)->name,
                'institute' => optional($student->institute)->name,
                'institute_code' => optional($student->institute)->institute_code,
            ];

            $previous_mark = $result->total_mark;
            $rank++;
        }

        return response()->json([
            'exam' => [
                'id' => $exam_id,
                'name' => optional($query->first()->exam)->name,
            ],
            'zamat' => [
                'id' => $zamat_id,
                'name' => optional($query->first()->zamat)->name,
            ],
            'group' => $group_id ? [
                'id' => $group_id,
                'name' => optional($query->first()->student->group)->name,
            ] : null,
            'students' => $rankedStudents,
        ], 200);
    }
}
