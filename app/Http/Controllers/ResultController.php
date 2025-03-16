<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
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
                'mark' => $result->mark ?? null, // ✅ এখন null থাকলে null-ই থাকবে, 0 থাকলে 0 থাকবে
            ];
        })->sortBy('id')->values();

        $total_mark = $subjects->sum(function ($subject) {
            return $subject['mark'] !== null ? $subject['mark'] : 0; // ✅ null থাকলে যোগ করবে না
        });

        $full_total_marks = $subjects->sum('full_marks');

        $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;

        $grade = $this->calculateGrade($percentage);

        if ($grade === null || $subjects->contains('mark', null)) {
            $grade = null;
        }

        $response = [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'registration_number' => $student->registration_number,
                'group' => optional($student->group)->name,
                'institute' => optional($student->institute)->name,
                'institute_code' => optional($student->institute)->institute_code,
                'merit' => $student->merit,
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
            'mark' => $result->mark ?? null,
        ];

        return response()->json($response, 200);
    }

    private function convertToBanglaNumber($number)
    {
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

        $ordinalSuffixes = [
            1 => 'ম',
            2 => 'য়',
            3 => 'য়',
            4 => 'র্থ',
            5 => 'ম',
            6 => 'ষ্ঠ',
            7 => 'ম',
            8 => 'ম',
            9 => 'ম',
            10 => 'ম',
        ];

        $banglaNumber = str_replace($englishDigits, $banglaDigits, $number);

        if (array_key_exists($number, $ordinalSuffixes)) {
            return $banglaNumber . $ordinalSuffixes[$number];
        } else {
            return $banglaNumber . 'তম';
        }
    }

    private function calculateGrade($percentage)
    {
        if ($percentage == 0 || $percentage === null) {
            return 'অনুপস্থিত';
        }
        if ($percentage >= 80) {
            return 'মুমতায';
        } elseif ($percentage >= 65) {
            return 'জায়্যিদ জিদ্দান';
        } elseif ($percentage >= 50) {
            return 'জায়্যিদ';
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
                'examSubject',
                'student.group',
                'student.institute'
            ])
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 404);
        }

        $subjects = $results->pluck('examSubject.subject')->unique('id')->map(function ($subject) use ($results) {
            $examSubject = $results->firstWhere('examSubject.subject.id', $subject->id)->examSubject;
            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'full_marks' => $examSubject ? $examSubject->full_marks : null,
                'pass_marks' => $examSubject ? $examSubject->pass_marks : null
            ];
        })
            ->sortBy('id')->values();

        $group = null;
        $firstStudent = $results->first()->student;
        if ($firstStudent && $firstStudent->group) {
            $group = [
                'id' => $firstStudent->group->id,
                'name' => $firstStudent->group->name,
            ];
        }

        $studentsResults = $results->groupBy('student.id')->map(function ($studentResults) use ($subjects) {
            $student = $studentResults->first()->student;

            $hasNullMark = $studentResults->contains(function ($result) {
                return $result->mark === null;
            });

            $marks = $subjects->map(function ($subject) use ($studentResults) {
                $mark = optional($studentResults->firstWhere('examSubject.subject.id', $subject['id']))->mark;
                return $mark === null ? null : (float) $mark; // `null` থাকলে সেটাই রিটার্ন করবে
            });

            $total_mark = $marks->sum();

            $full_marks = $studentResults->map(function ($result) {
                return (float) ($result->examSubject->full_marks ?? 0);
            });

            $full_total_marks = $full_marks->sum();

            $percentage = $full_total_marks > 0 ? round(($total_mark / $full_total_marks) * 100, 2) : 0;

            $grade = $hasNullMark ? null : $this->calculateGrade($percentage);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $total_mark,
                'percentage' => $percentage,
                'grade' => $grade,
                'marks' => $marks->values(),
                'merit' => $student->merit,
            ];
        })->values();

        $studentsResults = $studentsResults->sortBy('roll_number')->values();

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
                'name' => optional($results->first()->exam)->name,
            ],
            'subjects' => $subjects,
            'students' => $studentsResults,
        ];

        if ($group) {
            $response['group'] = $group;
        }

        return response()->json($response, 200);
    }

    public function getMeritList(Request $request, $exam_id, $zamat_id, $group_id = null)
    {
        $limit = $request->input('limit', 10);

        $results = Result::query()
            ->whereHas('zamat', function ($query) use ($zamat_id) {
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
                'student.institute',
                'exam:id,name',
                'zamat' => function ($query) {
                    $query->select('id', 'name', 'department_id')->with('department:id,name');
                }
            ])
            ->select('results.student_id', 'results.exam_id', 'results.zamat_id')
            ->selectRaw('student_id, SUM(mark) as total_mark, SUM(exam_subjects.full_marks) as full_marks')
            ->join('exam_subjects', 'results.exam_subject_id', '=', 'exam_subjects.id')
            ->groupBy('results.student_id', 'results.exam_id', 'results.zamat_id')
            ->having('total_mark', '>', 0)
            ->orderByDesc('total_mark')
            ->orderBy('results.student_id')
            ->limit($limit)
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No merit list found'], 404);
        }

        $rankedStudents = [];
        $rank = 1;
        $previous_mark = null;
        $suffix_index = 0;
        $suffixes = ['ক', 'খ', 'গ', 'ঘ', 'ঙ', 'চ', 'ছ', 'জ', 'ঝ', 'ঞ', 'ট', 'ঠ', 'ড', 'ঢ', 'ণ', 'ত', 'থ', 'দ', 'ধ', 'ন', 'প', 'ফ', 'ব', 'ভ', 'ম', 'য', 'র', 'ল', 'শ', 'ষ', 'স', 'হ', 'ড়', 'ঢ়', 'য়'];

        foreach ($results as $key => $result) {
            if ($key && $result->total_mark !== $previous_mark) {
                $rank++;
            }

            $student = $result->student;
            $percentage = $result->full_marks > 0 ? round(($result->total_mark / $result->full_marks) * 100, 2) : 0;
            $grade = $this->calculateGrade($percentage);

            if ($result->total_mark !== $previous_mark) {
                $rank_suffix = "(" . $suffixes[0] . ")";
                $suffix_index = 1;
            } else {
                $rank_suffix = "(" . ($suffixes[$suffix_index] ?? '-') . ")";
                $suffix_index++;
            }

            $rankedStudents[] = [
                'rank' => $this->convertToBanglaNumber($rank),
                'suffix' => $rank_suffix,
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $result->total_mark,
                'percentage' => $percentage,
                'grade' => $grade,
                'institute' => optional($student->institute)->name,
                'institute_code' => optional($student->institute)->institute_code,
            ];

            $previous_mark = $result->total_mark;
        }

        $lastIndex = count($rankedStudents) - 1;
        foreach ($rankedStudents as $index => &$rankedStudent) {
            if ($rankedStudent["suffix"] == "(ক)") {
                if ($index == $lastIndex || $rankedStudent["total_mark"] != $rankedStudents[$index + 1]["total_mark"]) {
                    $rankedStudent["suffix"] = "";
                }
            }
        }

        return response()->json([
            'exam' => [
                'id' => $exam_id,
                'name' => optional($results->first()->exam)->name,
            ],
            'zamat' => [
                'id' => $zamat_id,
                'name' => optional($results->first()->zamat)->name,
                'department' => optional($results->first()->zamat->department)->name, // ✅ এখন ঠিকমতো department আসবে!
            ],
            'group' => $group_id ? [
                'id' => $group_id,
                'name' => optional($results->first()->student->group)->name,
            ] : null,
            'students' => $rankedStudents,
        ], 200);
    }

    public function updateMerit(Request $request)
    {
        $meritData = $request->input('merit');

        if (!$meritData || !is_array($meritData)) {
            return response()->json(['message' => 'Invalid merit data'], 400);
        }

        foreach ($meritData as $data) {
            if (isset($data['student_id'], $data['rank'])) {
                Student::where('id', $data['student_id'])->update(['merit' => $data['rank']]);
            }
        }

        return response()->json(['message' => 'Merit list updated successfully'], 200);
    }

    public function printMeritList(Request $request, $exam_id, $zamat_id, $group_id = null)
    {
        $results = Result::query()
            ->whereHas('student', function ($query) {
                $query->whereNotNull('merit');
            })
            ->whereHas('zamat', function ($query) use ($zamat_id) {
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
                'student.institute',
                'exam:id,name',
                'zamat' => function ($query) {
                    $query->select('id', 'name', 'department_id')->with('department:id,name');
                }
            ])
            ->select('results.student_id', 'results.exam_id', 'results.zamat_id')
            ->selectRaw('student_id, SUM(mark) as total_mark, SUM(exam_subjects.full_marks) as full_marks')
            ->join('exam_subjects', 'results.exam_subject_id', '=', 'exam_subjects.id')
            ->groupBy('results.student_id', 'results.exam_id', 'results.zamat_id')
            ->having('total_mark', '>', 0)
            ->orderByDesc('total_mark')
            ->orderBy('results.student_id')
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No merit list found'], 404);
        }

        $studentsList = $results->map(function ($result) {
            $student = $result->student;
            $percentage = $result->full_marks > 0 ? round(($result->total_mark / $result->full_marks) * 100, 2) : 0;
            $grade = $this->calculateGrade($percentage);

            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'total_mark' => $result->total_mark,
                'percentage' => $percentage,
                'grade' => $grade,
                'institute' => optional($student->institute)->name,
                'institute_code' => optional($student->institute)->institute_code,
                'merit' => $student->merit,
            ];
        });

        return response()->json([
            'exam' => [
                'id' => $exam_id,
                'name' => optional($results->first()->exam)->name,
            ],
            'zamat' => [
                'id' => $zamat_id,
                'name' => optional($results->first()->zamat)->name,
                'department' => optional($results->first()->zamat->department)->name, // ✅ সঠিকভাবে ডিপার্টমেন্ট লোড করা হয়েছে
            ],
            'group' => $group_id ? [
                'id' => $group_id,
                'name' => optional($results->first()->student->group)->name,
            ] : null,
            'students' => $studentsList,
        ], 200);
    }
}
