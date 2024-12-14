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
            'institute:id,name,institute_code',
            'center:id,name,institute_code',
            'group:id,name'
        ]);

        if ($request->has('registration_number') && $request->registration_number) {
            $query->where('registration_number', $request->registration_number);
        }

        if ($request->has('application_id') && $request->application_id) {
            $query->where('application_id', $request->application_id);
        }

        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->has('zamat_id') && $request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
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

    public function PrintStudents(Request $request)
    {
        $query = Student::with([
            'exam:id,name',
            'zamat:id,name,department_id',
            'zamat.department:id,name',
            'institute:id,name,institute_code',
            'center:id,name,institute_code',
            'group:id,name'
        ]);

        if ($request->has('application_id') && $request->application_id) {
            $query->where('application_id', $request->application_id);
        }

        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }

        if ($request->has('zamat_id') && $request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
        }

        $students = $query->get();

        return response()->json($students);
    }

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
        $data = Student::with(['center', 'zamat'])
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

    public function PrintEnvelop(Request $request)
    {
        $areaName = $request->input('area_name');
        $instituteCode = $request->input('institute_code');

        $query = DB::table('students')
            ->join('institutes', 'students.institute_id', '=', 'institutes.id')
            ->join('areas', 'institutes.area_id', '=', 'areas.id')
            ->join('zamats', 'students.zamat_id', '=', 'zamats.id')
            ->select(
                'areas.name as area_name',
                'institutes.name as institute_name',
                'institutes.institute_code',
                'institutes.phone',
                'zamats.name as zamat_name',
                DB::raw('COUNT(students.id) as student_count')
            )
            ->groupBy('areas.name', 'institutes.name', 'institutes.institute_code', 'institutes.phone', 'zamats.name');


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
            'institute_code' => 'required|string|exists:institutes,institute_code',
            'zamat_id' => 'nullable|exists:zamats,id',
            'updates' => 'required|array',
            'updates.center_id' => 'nullable|exists:centers,id',
        ]);

        $studentsQuery = Student::query()
            ->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });

        if ($request->zamat_id) {
            $studentsQuery->where('zamat_id', $request->zamat_id);
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No students found for the given criteria'], 404);
        }

        $updates = $request->input('updates');
        try {
            foreach ($students as $student) {
                $student->update($updates);
            }

            return response()->json([
                'message' => 'Students updated successfully',
                'updated_count' => $students->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
