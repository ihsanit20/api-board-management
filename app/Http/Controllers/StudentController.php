<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with([
            'exam:id,name',
            'zamat:id,name',
            'area:id,name',
            'institute:id,name,institute_code',
            'center',
            'group:id,name'
        ])->get();

        return response()->json($students);
    }

    public function show($id)
    {
        $student = Student::with([
            'exam:id,name',
            'zamat:id,name',
            'area:id,name',
            'institute:id,name,institute_code',
            'center',
            'group:id,name'
        ])->findOrFail($id);

        return response()->json($student);
    }

    public function store(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'area_id' => 'nullable|exists:areas,id',
            'center_id' => 'nullable|exists:centers,id',
            'name' => 'required|string|max:255',
            'name_arabic' => 'nullable|string|max:255',
            'father_name' => 'required|string|max:255',
            'father_name_arabic' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'address' => 'nullable|string|max:255',
            'roll_number' => 'required|string|max:5|unique:students,roll_number',
            'registration_number' => 'required|string|max:9|unique:students,registration_number',
        ]);

        try {
            $student = Student::create($request->all());

            return response()->json(['message' => 'Student added successfully', 'student' => $student], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to add student', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'name_arabic' => 'nullable|string|max:255',
            'father_name' => 'sometimes|required|string|max:255',
            'father_name_arabic' => 'nullable|string|max:255',
            'date_of_birth' => 'sometimes|required|date|before:today',
            'address' => 'nullable|string|max:255',
            'roll_number' => 'sometimes|required|string|max:5|unique:students,roll_number,' . $id,
            'registration_number' => 'sometimes|required|string|max:9|unique:students,registration_number,' . $id,
        ]);

        try {
            $student->update($request->all());

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
