<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Student;
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

    public function publicShow(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'institute_id' => 'required|exists:institutes,id',
        ]);

        $application = Application::query()
            ->where('id', $request->application_id)
            ->where('institute_id', $request->institute_id)
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
            ->first();

        if (!$application) {
            return response()->json(['message' => 'Application not found or does not belong to the provided institute.'], 404);
        }

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
            'center_id' => 'nullable|exists:institutes,id',
            
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
            'payment_status' => 'required|in:Pending,Paid',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application->update(['payment_status' => $request->payment_status]);

            if ($request->payment_status === 'Paid') {
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
                        'registration_number' => $this->generateRegistrationNumber(),
                    ]);
                }
            }

            return response()->json(['message' => 'Payment status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment status', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateRegistrationNumber()
    {
        return str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

}
