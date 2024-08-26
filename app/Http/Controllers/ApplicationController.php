<?php

namespace App\Http\Controllers;

use App\Models\Application;
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

        return response()->json(['message' => 'Application status updated successfully', 'application' => $application]);
    }
}
