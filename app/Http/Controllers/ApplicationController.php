<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Msilabs\Bkash\BkashPayment;

class ApplicationController extends Controller
{
    use BkashPayment;

    private static $application = null;

    public function index(Request $request)
    {
        $query = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center',
                'submittedBy',
                'approvedBy',
                'group:id,name'
            ]);
    
        // ফিল্টারিং প্যারামিটার চেক করা
        if ($request->has('zamat_id') && $request->zamat_id) {
            $query->where('zamat_id', $request->zamat_id);
        }
    
        if ($request->has('institute_code') && $request->institute_code) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }
    
        if ($request->has('application_id') && $request->application_id) {
            $query->where('id', $request->application_id);
        }
    
        // ফিল্টার করা ডেটা ফেচ করা
        $applications = $query->get();
    
        // JSON রেসপন্স রিটার্ন করা
        return response()->json($applications);
    }
    
    public function getApplicationCounts(Request $request)
    {
        // সাধারণ ফিল্টারিং যদি প্রয়োজন হয় (উদাহরণস্বরূপ `zamat_id`, `institute_code`)
        $query = Application::query();
    
        // মোট নিবন্ধনের সংখ্যা গণনা করা
        $totalApplications = $query->count();
    
        // পেন্ডিং নিবন্ধনের সংখ্যা গণনা করা
        $pendingApplications = (clone $query)->where('payment_status', 'Pending')->count();
    
        // পেইড নিবন্ধনের সংখ্যা গণনা করা
        $paidApplications = (clone $query)->where('payment_status', 'Paid')->count();
    
        // JSON রেসপন্সে সংখ্যা ফেরত দেওয়া
        return response()->json([
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'paidApplications' => $paidApplications
        ]);
    }
    

    public function show($id)
    {
        $application = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
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
            
            'gender' => 'nullable|in:male,female',

            'students' => 'required|array|min:1',

            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date|before:today',
            'students.*.para' => 'nullable|string|max:255',
            'students.*.address' => 'nullable|string|max:255',

            'total_amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string|in:Online,Offline',
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
                'payment_method' => $request->payment_method ?? 'Offline',
                'submitted_by' => Auth::id(),
                'students' => $request->students,
            ]);

            return response()->json([
                'message' => 'Application submitted successfully', 
                'application' => $application
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to submit application', 'error' => $e->getMessage()], 500);
        }
    }

    public function bkashCreatePayment(Application $application)
    {
        $response = $this->initiateOnlinePayment($application);

        return response()->json([
            'message'   => 'Application submitted successfully. Redirecting to payment gateway...',
            'response'  => $response,
            'success'   => !!($response->bkashURL ?? false),
            'bkashURL'  => $response->bkashURL ?? '#'
        ], 201);
    }

    private function initiateOnlinePayment($application)
    {
        $callback_url = env('FRONTEND_BASE_URL', 'https://tanjim.madrasah.cc') . "/bkash/callback/{$application->id}/{$application->institute_id}";

        try {
            $response = $this->createPayment($application->total_amount, $application->id, $callback_url);
            
            return $response;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to initiate payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function bkashExecutePayment(Application $application, Request $request)
    {
        $paymentID = $request->input('paymentID');

        if($paymentID) {
            $response = $this->executePayment($paymentID);

            // return $response;
      
            if($response->transactionStatus == 'Completed') {

                // store payment data
                $application->update(['payment_method' => 'Online']); 

                $request->merge(['payment_status' => 'Paid']);

                self::$application = $application;
           
                $this->updatePaymentStatus($request, $application->id); // how to call this

                return response()->json([
                    'message' => 'Payment success',
                    'status' => (boolean) (true),
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Payment failed! Try Again!',
                    'status' => (boolean) (false),
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'Payment failed! Try Again',
                'status' => (boolean) (false),
            ], 200);
        }
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:Pending,Paid',
        ]);

        try {
            $application = self::$application ?? Application::findOrFail($id);
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
                        'para' => $studentData['para'],
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
