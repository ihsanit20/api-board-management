<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Student;
use Illuminate\Http\Request;

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
        ]);

        try {
            $student->update([
                'name' => $request->name,
                'father_name' => $request->father_name,
                'date_of_birth' => $request->date_of_birth,
                'address' => $request->address,
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
}
