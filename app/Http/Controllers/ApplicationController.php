<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::with(['exam', 'zamat', 'institute'])->get();
        return response()->json($applications);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',

            'students' => 'required|array|min:1',

            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date',
            'students.*.address' => 'nullable|string|max:255',

            'total_amount' => 'required|numeric',
        ]);

        $application = Application::create([
            'exam_id' => $request->exam_id,
            'institute_id' => $request->institute_id,
            'zamat_id' => $request->zamat_id,
            'status' => 'Pending',
            'payment_status' => 'Pending',
            'total_amount' => $request->total_amount,
            'submitted_by' => Auth::id(),
            'students' => $request->students,
        ]);

        return response()->json(['message' => 'Application submitted successfully', 'application' => $application], 201);
    }

    public function show($id)
    {
        $application = Application::with(['exam', 'zamat', 'institute', 'students'])->findOrFail($id);
        return response()->json($application);
    }

    public function update(Request $request, $id)
    {
        $application = Application::findOrFail($id);

        $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $application->update([
            'status' => $request->status,
            'approved_by' => Auth::id(),
        ]);

        if ($request->status === 'Approved' && $application->payment_method === 'Offline') {
            $this->completePayment($application);
        }

        return response()->json(['message' => 'Application updated successfully']);
    }

    public function destroy($id)
    {
        $application = Application::findOrFail($id);
        $application->delete();
        return response()->json(['message' => 'Application deleted successfully']);
    }

    private function completePayment($application)
    {
        $application->update(['payment_status' => 'Completed']);

        foreach ($application->students as $student) {
            Student::create([
                'registration' => $student->registration,
                'name' => $student->name,
                'name_arabic' => $student->name_arabic,
                'father_name' => $student->father_name,
                'father_name_arabic' => $student->father_name_arabic,
                'date_of_birth' => $student->date_of_birth,
                'address' => $student->address,
                'area_id' => $student->area_id,
                'institute_id' => $application->institute_id,
                'zamat_id' => $application->zamat_id,
                'exam_id' => $application->exam_id,
            ]);
        }
    }
}
