<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Institute;
use App\Models\Student;
use App\Models\Zamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $lastExam = Exam::latest()->first();

        $query = Student::with([
            'exam:id,name',
            'zamat:id,name',
            'area:id,name',
            'institute:id,name,institute_code,phone',
            'center:id,name',
            'group:id,name'
        ]);

        if ($request->has('registration_number') && $request->registration_number) {
            $query->where('registration_number', $request->registration_number);
        }

        if ($request->has('roll_number') && $request->roll_number) {
            $query->where('roll_number', $request->roll_number);
        }

        if ($request->has('application_id') && $request->application_id) {
            $query->where('application_id', $request->application_id);
        }

        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->has('exam_id') && $request->exam_id) {
            $query->where('exam_id', $request->exam_id);
        } else {
            if ($lastExam) {
                $query->where('exam_id', $lastExam->id);
            }
        }

        $students = $query->get();

        return response()->json($students);
    }

    // public function PrintStudents(Request $request)
    // {
    //     $query = Student::with([
    //         'exam:id,name',
    //         'zamat:id,name,department_id',
    //         'zamat.department:id,name',
    //         'institute:id,name,institute_code',
    //         'center:id,name,institute_code',
    //         'group:id,name'
    //     ]);

    //     if ($request->has('application_id') && $request->application_id) {
    //         $query->where('application_id', $request->application_id);
    //     }

    //     if ($request->has('institute_code') && $request->institute_code) {
    //         $query->whereHas('institute', function ($q) use ($request) {
    //             $q->where('institute_code', $request->institute_code);
    //         });
    //     }

    //     if ($request->has('zamat_id') && $request->zamat_id) {
    //         $query->where('zamat_id', $request->zamat_id);
    //     }

    //     $students = $query->get();

    //     return response()->json($students);
    // }

    public function show($id)
    {
        $student = Student::with([
            'exam:id,name',
            'zamat:id,name',
            'area:id,name',
            'institute:id,name,institute_code',
            'center:id,name',
            'group:id,name'
        ])->findOrFail($id);

        return response()->json($student);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'father_name' => 'sometimes|required|string|max:255',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'address' => 'nullable|string|max:255',
            'zamat_id' => 'sometimes|required|exists:zamats,id',
            'para' => 'nullable|string|max:255',
        ]);

        try {
            $student->update([
                'name' => $request->name,
                'father_name' => $request->father_name,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
                'zamat_id' => $request->zamat_id,
                'para' => $request->para,
            ]);

            return response()->json(['message' => 'Student updated successfully', 'student' => $student]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update student', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        try {
            $student->delete();

            return response()->json(['message' => 'Student deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete student', 'error' => $e->getMessage()], 500);
        }
    }

    public function centerWiseStudentCount()
    {
        $lastExam = Exam::latest()->first();

        $data = Student::query()
            ->with(['center', 'zamat'])
            ->where('exam_id', $lastExam->id)
            ->select('center_id', 'zamat_id', DB::raw('COUNT(id) as student_count'))
            ->groupBy('center_id', 'zamat_id')
            ->get()
            ->mapToGroups(function ($item) {
                return [
                    optional($item->center)->name => [
                        'zamat_name' => optional($item->zamat)->name,
                        'student_count' => $item->student_count
                    ]
                ];
            });

        return response()->json($data);
    }

    public function centerWiseInstituteCount()
    {
        $lastExam = Exam::latest()->first();

        $data = Student::with(['center', 'zamat', 'institute'])
            ->select(
                'center_id',
                'zamat_id',
                'institute_id',
                DB::raw('COUNT(id) as student_count')
            )
            ->whereNotNull('roll_number')
            ->where('exam_id', $lastExam->id)
            ->groupBy('center_id', 'zamat_id', 'institute_id')
            ->get()
            ->groupBy('center_id')
            ->map(function ($centerGroup) {
                $centerName = optional($centerGroup->first()->center)->name;
                $zamatCounts = $centerGroup->groupBy('zamat_id')->map(function ($zamatGroup, $zamatId) {
                    $zamatName = optional($zamatGroup->first()->zamat)->name;
                    return [
                        'zamat_name' => $zamatName,
                        'student_count' => $zamatGroup->sum('student_count')
                    ];
                });
                $instituteCounts = $centerGroup->groupBy('institute_id')->map(function ($instituteGroup) {
                    return [
                        'institute_name' => optional($instituteGroup->first()->institute)->name,
                        'institute_code' => optional($instituteGroup->first()->institute)->institute_code,
                        'phone' => optional($instituteGroup->first()->institute)->phone,
                        'zamats' => $instituteGroup->groupBy('zamat_id')->map(function ($zamatGroup, $zamatId) {
                            $zamatName = optional($zamatGroup->first()->zamat)->name;
                            return [
                                'zamat_name' => $zamatName,
                                'student_count' => $zamatGroup->sum('student_count')
                            ];
                        })
                    ];
                });

                return [
                    'center' => $centerName,
                    'zamats' => $zamatCounts,
                    'Total' => $centerGroup->sum('student_count'),
                    'institutes' => $instituteCounts
                ];
            });

        return response()->json($data);
    }

    public function PrintEnvelop(Request $request)
    {
        $areaName = $request->input('area_name');
        $instituteCode = $request->input('institute_code');

        $lastExamId = Exam::max('id'); // সর্বশেষ exam id

        $query = DB::table('applications')
            ->join('institutes', 'institutes.id', '=', 'applications.institute_id')
            ->join('areas', 'institutes.area_id', '=', 'areas.id')
            ->leftJoin('zamats', 'zamats.id', '=', 'applications.zamat_id')
            ->select([
                'areas.name as area_name',
                'institutes.name as institute_name',
                'institutes.institute_code',
                'institutes.phone',
                'zamats.id as zamat_id',
                'zamats.name as zamat_name',
                DB::raw('SUM(JSON_LENGTH(COALESCE(applications.students, JSON_ARRAY()))) as student_count'),
            ])
            ->where('applications.exam_id', $lastExamId)
            // ->where('applications.payment_status', 'Paid')
            ->groupBy(
                'areas.name',
                'institutes.name',
                'institutes.institute_code',
                'institutes.phone',
                'zamats.id',
                'zamats.name'
            )
            ->orderBy('institutes.institute_code')
            ->orderBy('zamats.id')
            ;

        if ($areaName) {
            $query->where('areas.name', $areaName);
        }

        if ($instituteCode) {
            $query->where('institutes.institute_code', $instituteCode);
        }

        $data = $query->get()
            ->groupBy('area_name')
            ->map(function ($area) {
                return $area->groupBy('institute_name')->map(function ($institutes) {
                    $institute = $institutes->first();
                    return [
                        'institute_name' => $institute->institute_name,
                        'institute_code' => $institute->institute_code,
                        'phone' => $institute->phone,
                        'zamat_counts' => $institutes->map(function ($item) {
                            return [
                                'zamat_name' => $item->zamat_name,
                                'student_count' => $item->student_count,
                            ];
                        })->values()
                    ];
                })->values();
            });

        return response()->json($data);
    }

    public function studentsWithoutRollNumber(Request $request)
    {
        $institute = Institute::query()
            ->select(
                'id',
                'name',
                'phone',
                'institute_code',
            )
            ->where('institute_code', $request->institute_code)
            ->firstOrFail();

        $zamat = Zamat::query()
            ->select(
                'id',
                'name',
                'department_id',
            )
            ->with('department:id,name')
            ->findOrFail($request->zamat_id);

        // return
        $students = Student::query()
            ->select(
                'id',
                'name',
                'registration_number',
                'father_name',
                'date_of_birth',
            )
            ->where('institute_id', $institute->id)
            ->where('zamat_id', $zamat->id)
            ->whereNull('roll_number')
            ->get();

        return response()->json([
            'students' => $students,
            'institute' => $institute,
            'zamat' => $zamat,
        ]);
    }

    public function studentsWithRollNumber(Request $request)
    {
        $institute = Institute::query()
            ->select('id', 'name', 'phone', 'institute_code')
            ->where('institute_code', $request->institute_code)
            ->firstOrFail();

        $zamat = Zamat::query()
            ->select('id', 'name', 'department_id')
            ->with('department:id,name')
            ->findOrFail($request->zamat_id);

        $students = Student::query()
            ->select('id', 'name', 'registration_number', 'father_name', 'date_of_birth', 'roll_number')
            ->where('institute_id', $institute->id)
            ->where('zamat_id', $zamat->id)
            ->whereNotNull('roll_number')
            ->get();

        return response()->json([
            'students' => $students,
            'institute' => $institute,
            'zamat' => $zamat,
        ]);
    }

    public function studentsAdmitCard(Request $request)
    {
        $query = Student::with([
            'exam:id,name',
            'zamat:id,name,department_id',
            'zamat.department:id,name',
            'institute:id,name,institute_code',
            'center:id,name,institute_code',
            'group:id,name'
        ])->whereNotNull('roll_number');

        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->has('zamat_id') && $request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
        }

        if ($request->has('roll_number') && $request->roll_number) {
            $query->where('roll_number', $request->roll_number);
        }

        $students = $query->get();

        return response()->json($students);
    }

    public function MultipleUpdate(Request $request)
    {
        $request->validate([
            'institute_code'    => 'required|string|exists:institutes,institute_code',
            'zamat_id'          => 'required|exists:zamats,id',
            'new_center_id'     => 'required|exists:institutes,id',
        ]);

        $last_exam = Exam::latest()->first();

        $center_exists = Institute::query()
            ->where('id', $request->new_center_id)
            ->where('is_center', 1)
            ->exists();

        if ($center_exists) {
            $students = Student::query()
                ->where('exam_id', $last_exam->id)
                ->where('zamat_id', $request->zamat_id)
                ->whereHas('institute', function ($q) use ($request) {
                    $q->where('institute_code', $request->institute_code);
                })
                ->update([
                    'center_id' => $request->new_center_id,
                ]);
        }

        return response()->json([
            'is_success' => (bool) ($students ?? false)
        ]);
    }

    public function rollNumberCounts()
    {
        $studentsWithRollCount = Student::whereNotNull('roll_number')->count();
        $institutesWithRollCount = Student::whereNotNull('roll_number')->distinct('institute_id')->count('institute_id');

        $studentsWithoutRollCount = Student::whereNull('roll_number')->count();
        $institutesWithoutRollCount = Student::whereNull('roll_number')->distinct('institute_id')->count('institute_id');

        return response()->json([
            'with_roll' => [
                'student_count' => $studentsWithRollCount,
                'institute_count' => $institutesWithRollCount,
            ],
            'without_roll' => [
                'student_count' => $studentsWithoutRollCount,
                'institute_count' => $institutesWithoutRollCount,
            ],
        ]);
    }

    public function withoutAndWithRollNumberCount()
    {
        // সর্বশেষ পরীক্ষার আইডি পাওয়া
        $lastExam = Exam::latest()->first();

        if (!$lastExam) {
            return response()->json(['error' => 'No exam data found.'], 404);
        }

        $lastExamId = $lastExam->id;

        // ইনস্টিটিউট ফিল্টার করা রোল নাম্বার সহ ও রোল নাম্বার ছাড়া শিক্ষার্থীদের উপর ভিত্তি করে
        $institutes = Institute::query()
            ->whereHas('students', function ($query) use ($lastExamId) {
                $query->where('exam_id', $lastExamId);
            })
            ->with([
                'students' => function ($query) use ($lastExamId) {
                    $query->where('exam_id', $lastExamId)->select('id', 'institute_id', 'zamat_id', 'roll_number');
                },
                'students.zamat:id,name'
            ])
            ->get(['id', 'name', 'institute_code', 'phone']);

        // ডেটা প্রসেস করা
        $data = $institutes->map(function ($institute) {
            $zamatGroups = $institute->students->groupBy('zamat_id')->map(function ($students, $zamatId) {
                $studentsWithRoll = $students->whereNotNull('roll_number');
                $studentsWithoutRoll = $students->whereNull('roll_number');

                return [
                    'zamat_name' => optional($students->first()->zamat)->name,
                    'with_roll_count' => $studentsWithRoll->count(),
                    'without_roll_count' => $studentsWithoutRoll->count(),
                ];
            });

            return [
                'institute_name' => $institute->name,
                'institute_code' => $institute->institute_code,
                'phone' => $institute->phone,
                'zamat_details' => $zamatGroups->values(),
            ];
        });

        return response()->json($data);
    }

    public function areaWiseStudentCountByZamat()
    {
        $lastExam = Exam::latest()->first();

        if (!$lastExam) {
            return response()->json(['error' => 'No exam data found.'], 404);
        }

        $lastExamId = $lastExam->id;

        $data = Student::query()
            ->where('exam_id', $lastExamId)
            ->whereNotNull('roll_number')
            ->with([
                'area:id,name',
                'zamat:id,name'
            ])
            ->select('area_id', 'zamat_id', DB::raw('COUNT(id) as student_count'))
            ->groupBy('area_id', 'zamat_id')
            ->orderBy('area_id')
            ->orderBy('zamat_id')
            ->get()
            ->groupBy('area_id')
            ->map(function ($students) {
                $totalStudentCount = $students->sum('student_count');

                return [
                    'area_name' => optional($students->first()->area)->name,
                    'total_student_count' => $totalStudentCount,
                    'zamats' => $students->map(function ($zamatGroup) {
                        return [
                            'zamat_name' => optional($zamatGroup->zamat)->name,
                            'student_count' => $zamatGroup->student_count,
                        ];
                    })->values(),
                ];
            });

        return response()->json($data);
    }
}
