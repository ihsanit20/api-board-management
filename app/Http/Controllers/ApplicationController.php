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

    public function index()
    {
        $applications = Application::query()
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
            ->when(request()->institute_code, function ($query, $institute_code) {
                $query->whereHas('institute', function ($query) use ($institute_code) {
                    $query->where('institute_code', $institute_code);
                });
            })
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
            'application_id' => 'required',
            'institute_code' => 'required',
        ]);

        $application = Application::query()
            ->where('id', $request->application_id)
            ->whereHas('institute', function ($query) use ($request) {
                $query->where('institute_code', $request->institute_code);
            })
            ->with([
                'exam:id,name',
                'zamat:id,name',
                'area:id,name,area_code',
                'institute:id,name,institute_code',
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
                // 'gender' => $request->gender,
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

            if ($request->payment_status === 'Paid' && !Student::where('application_id', $application->id)->exists()) {
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
                        'name_arabic' => $studentData['name_arabic'],
                        'father_name' => $studentData['father_name'],
                        'father_name_arabic' => $studentData['father_name_arabic'],
                        'date_of_birth' => $studentData['date_of_birth'],
                        'para' => $studentData['para'],
                        'address' => $studentData['address'],
                        // 'gender' => $application->gender,
                        'registration_number' => $this->generateRegistrationNumber($application->exam_id, $registration_numbers),
                    ]);
                }
            }

            $application->update(['payment_status' => $request->payment_status]);

            return response()->json(['message' => 'Payment status updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment status', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateRegistrationNumber($exam_id, &$previous_registration_numbers)
    {
        do {
            $rand = rand(100000, 999999); // Random number
            $new_registration_number = $exam_id . $rand;
        } while (in_array($new_registration_number, $previous_registration_numbers));
    
        // Push the new registration number into the array
        $previous_registration_numbers[] = $new_registration_number;
    
        return $new_registration_number;
    }    
}
