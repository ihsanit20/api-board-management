<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Student; // Student মডেল ইম্পোর্ট করতে হবে
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name',
                'center',
                'submittedBy',
                'approvedBy',
                'group:id,name'
            ])
            ->get();

        return response()->json($applications);
    }

    public function show($id)
    {
        $application = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name',
                'center',
                'submittedBy',
                'approvedBy',
                'group:id,name'
            ])
            ->findOrFail($id);

        return response()->json($application);
    }

    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'area_id' => 'nullable|exists:areas,id',
            'center_id' => 'nullable|exists:centers,id',
            
            'gender' => 'required|in:male,female',

            'students' => 'required|array|min:1',

            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date|before:today',
            'students.*.address' => 'nullable|string|max:255',

            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:Online,Offline',
        ]);

        try {
            $application = Application::create([
                'exam_id' => $request->exam_id,
                'area_id' => $request->area_id,
                'institute_id' => $request->institute_id,
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
                'center_id' => $request->center_id,
                'gender' => $request->gender,
                'status' => 'Pending',
                'payment_status' => 'Pending',
                'total_amount' => $request->total_amount,
                'payment_method' => $request->payment_method,
                'submitted_by' => Auth::id(),
                'students' => $request->students,
            ]);

            return response()->json(['message' => 'Application submitted successfully', 'application' => $application], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to submit application', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,Paid,Failed',
        ]);

        $application = Application::findOrFail($id);

        $application->update([
            'payment_status' => $request->status,
        ]);

        // Check if both payment status is 'Paid' and application status is 'Approved'
        if ($request->status === 'Paid' && $application->status === 'Approved') {
            $this->storeStudents($application);
        }

        return response()->json(['message' => 'Payment status updated successfully', 'application' => $application]);
    }

    public function updateApplicationStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Pending,Approved,Rejected',
        ]);

        $application = Application::findOrFail($id);

        $application->update([
            'status' => $request->status,
            'approved_by' => $request->status === 'Approved' ? Auth::id() : null,
        ]);

        // Check if both payment status is 'Paid' and application status is 'Approved'
        if ($request->status === 'Approved' && $application->payment_status === 'Paid') {
            $this->storeStudents($application);
        }

        return response()->json(['message' => 'Application status updated successfully', 'application' => $application]);
    }

    private function storeStudents($application)
    {
        foreach ($application->students as $studentData) {
            Student::create([
                'application_id' => $application->id,
                'exam_id' => $application->exam_id,
                'institute_id' => $application->institute_id,
                'zamat_id' => $application->zamat_id,
                'group_id' => $application->group_id,
                'area_id' => $application->area_id,
                'center_id' => $application->center_id,
                'name' => $studentData['name'],
                'name_arabic' => $studentData['name_arabic'],
                'father_name' => $studentData['father_name'],
                'father_name_arabic' => $studentData['father_name_arabic'],
                'date_of_birth' => $studentData['date_of_birth'],
                'address' => $studentData['address'],
                'gender' => $application->gender,
                'roll_number' => $this->generateUniqueRollNumber(),
                'registration_number' => $this->generateUniqueRegistrationNumber(),
            ]);
        }
    }

    private function generateUniqueRollNumber()
    {
        do {
            $rollNumber = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (Student::where('roll_number', $rollNumber)->exists());

        return $rollNumber;
    }

    private function generateUniqueRegistrationNumber()
    {
        do {
            $registrationNumber = str_pad(mt_rand(1, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Student::where('registration_number', $registrationNumber)->exists());

        return $registrationNumber;
    }
}
