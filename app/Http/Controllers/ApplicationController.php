<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Exam;
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
                'submittedBy:id,name',
                'approvedBy:id,name',
                'group:id,name'
            ]);
    
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
    
        $perPage = $request->input('per_page', 15); 
    
        if ($perPage === 'all') {
            $applications = $query->latest('id')->get();
    
            return response()->json([
                'data' => ApplicationResource::collection($applications),
                'total' => $applications->count(),
                'per_page' => $applications->count(),
                'current_page' => 1,
                'last_page' => 1,
            ]);
        }

        $applications = $query->latest('id')->paginate($perPage);

        ApplicationResource::withoutWrapping();

        return response()->json([
            'data' => ApplicationResource::collection($applications),
            'total' => $applications->total(), // পেজিনেটর থেকে মোট আইটেম সংখ্যা
            'per_page' => $applications->perPage(), // প্রতি পেজে আইটেম সংখ্যা
            'current_page' => $applications->currentPage(), // বর্তমান পেজ নম্বর
            'last_page' => $applications->lastPage(), // মোট পেজ সংখ্যা
        ]);
    }    

    public function printApplications(Request $request)
    {
        $query = Application::query()
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name',
                'institute:id,name,institute_code',
                'center:id,name,institute_code',
                'submittedBy:id,name',
                'approvedBy:id,name',
                'group:id,name',
                'students' 
            ]);

        // Apply filters based on request parameters
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

        // Fetch filtered applications
        $applications = $query->latest('id')->get();

        ApplicationResource::withoutWrapping();

        // Include students in the print view
        $applications = ApplicationResource::collection($applications->map(function ($application) {
            return new ApplicationResource($application, true); // Pass true to include students
        }));

        // JSON response for print
        return response()->json($applications);
    }
    
    public function getApplicationCounts(Request $request)
    {
       
        $query = Application::query();
    
        $totalApplications = $query->count();
    
        $pendingApplications = (clone $query)->where('payment_status', 'Pending')->count();
    
        $paidApplications = (clone $query)->where('payment_status', 'Paid')->count();

        $totalStudents = (clone $query)->selectRaw('SUM(JSON_LENGTH(students)) as total_students')->value('total_students');
    
        return response()->json([
            'totalApplications' => $totalApplications,
            'pendingApplications' => $pendingApplications,
            'paidApplications' => $paidApplications,
            'totalStudents' => (int) $totalStudents
        ]);
    }
    
    public function getZamatWiseCounts()
    {
        // Fetch counts of applications and student count grouped by zamat_id
        $zamatCounts = Application::query()
            ->select('zamat_id')
            ->selectRaw('COUNT(*) as total_applications')
            ->selectRaw('SUM(JSON_LENGTH(students)) as total_students')
            ->groupBy('zamat_id')
            ->with('zamat:id,name')
            ->get();
    
        // Format the response with zamat name and counts
        $formattedCounts = $zamatCounts->map(function ($item) {
            return [
                'zamat_id' => $item->zamat_id,
                'zamat_name' => $item->zamat->name ?? 'Unknown',
                'total_applications' => $item->total_applications,
                'total_students' => (int) $item->total_students,
            ];
        });
    
        // Return JSON response
        return response()->json($formattedCounts);
    }
    
    public function getUserWiseCounts()
    {
        // Fetch counts of applications grouped by submitted_by
        $userCounts = Application::query()
            ->select('submitted_by')
            ->selectRaw('COUNT(*) as total_applications')
            ->selectRaw('SUM(JSON_LENGTH(students)) as total_students')
            ->groupBy('submitted_by')
            ->with(['submittedBy:id,name']) // Assuming submittedBy is a user relationship
            ->get();
    
        // Format the response with user names and counts
        $formattedCounts = $userCounts->map(function ($item) {
            return [
                'submitted_by' => $item->submitted_by,
                'submitted_by_name' => $item->submittedBy->name ?? 'Unknown',
                'total_applications' => $item->total_applications,
                'total_students' => (int) $item->total_students,
            ];
        });
    
        // Return JSON response
        return response()->json($formattedCounts);
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
            'institute_code' => 'required|exists:institutes,institute_code', // Validate using institute_code
        ]);

        $application = Application::query()
            ->where('id', $request->application_id)
            ->whereHas('institute', function ($query) use ($request) {
                $query->where('institute_code', $request->institute_code); // Search by institute_code
            })
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
                'payment_status' => 'Pending',
                'total_amount' => $request->total_amount, // 
                'payment_method' => $request->payment_method ?? 'Offline',
                'submitted_by' => Auth::guard('sanctum')->id() ?? null,
                'application_date' => $request->application_date ?? now(),
                'students' => $request->students,
            ]);

            $application->load('institute');

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

            $application->update([
                'payment_status' => $request->payment_status,
                'approved_by' => Auth::guard('sanctum')->id() ?? null,
            ]);

            if ($request->payment_status === 'Paid' && !Student::where('application_id', $application->id)->exists())
            {
                $registration_numbers = Student::query()
                    ->where('exam_id', $application->exam_id)
                    ->pluck('registration_number')
                    ->toArray();

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
                        'name_arabic' => $studentData['name_arabic'] ?? '',
                        'father_name' => $studentData['father_name'] ?? '',
                        'father_name_arabic' => $studentData['father_name_arabic'] ?? '',
                        'date_of_birth' => $studentData['date_of_birth'] ?? '',
                        'para' => $studentData['para'] ?? '',
                        'address' => $studentData['address'] ?? '',
                        'registration_number' => $this->generateRegistrationNumber($application->exam_id, $registration_numbers),
                    ]);
                }
            }

            return response()->json(['message' => 'Payment status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment status', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateRegistrationNumber($exam_id, &$previous_registration_numbers)
    {
        do {
            $rand = rand(10000, 99999); 
            $new_registration_number = $exam_id . $rand;
        } while (in_array($new_registration_number, $previous_registration_numbers));
    
        $previous_registration_numbers[] = $new_registration_number;
    
        return $new_registration_number;
    }  

    public function updateRegistrationPart(Request $request, $id)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
            'group_id' => 'nullable|exists:groups,id',
            'area_id' => 'nullable|exists:areas,id',
            'center_id' => 'nullable|exists:institutes,id',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application->update([
                'exam_id' => $request->exam_id,
                'institute_id' => $request->institute_id,
                'zamat_id' => $request->zamat_id,
                'group_id' => $request->group_id,
                'area_id' => $request->area_id,
                'center_id' => $request->center_id,
            ]);

            return response()->json([
                'message' => 'Registration information updated successfully', 
                'application' => $application
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update registration information', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStudentsPart(Request $request, $id)
    {
        $request->validate([
            'students' => 'required|array|min:1',
            'students.*.name' => 'required|string|max:255',
            'students.*.name_arabic' => 'nullable|string|max:255',
            'students.*.father_name' => 'required|string|max:255',
            'students.*.father_name_arabic' => 'nullable|string|max:255',
            'students.*.date_of_birth' => 'required|date|before:today',
            'students.*.para' => 'nullable|string|max:255',
            'students.*.address' => 'nullable|string|max:255',
        ]);

        try {
            $application = Application::findOrFail($id);
            $application->update([
                'students' => $request->students,
            ]);

            return response()->json([
                'message' => 'Students information updated successfully',
                'application' => $application
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update students information',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}
