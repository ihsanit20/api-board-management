<?php

namespace App\Http\Controllers;

use App\Models\CollectFee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Msilabs\Bkash\BkashPayment;

class FeeCollectionController extends Controller
{
    use BkashPayment;

    public function index(Request $request)
    {
        $query = CollectFee::with([
            'exam:id,name', 
            'institute:id,name,institute_code', 
            'zamat:id,name'
        ]);
    
        $query->when(true, function ($q) {
            $q->where(function ($subQuery) {
                $subQuery->where('payment_method', '!=', 'online')
                         ->orWhere(function ($subSubQuery) {
                             $subSubQuery->where('payment_method', 'online')
                                         ->whereNotNull('transaction_id');
                         });
            });
        });
    
        if ($request->has('institute_code')) {
            $query->whereHas('institute', function ($q) use ($request) {
                $q->where('institute_code', $request->institute_code);
            });
        }
    
        if ($request->has('zamat_id')) {
            $query->where('zamat_id', $request->zamat_id);
        }
    
        if ($request->has('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
    
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
    
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('created_at', [$request->date_from, $request->date_to]);
        }

        if ($request->has('transaction_id')) {
            $query->where('transaction_id', $request->transaction_id);
        }
    
        $feeCollections = $query->orderBy('created_at', 'desc')->get();
    
        return response()->json([
            'message' => 'Fee collection list retrieved successfully.',
            'data' => $feeCollections,
        ], 200);
    }
    
    public function show($id)
    {
        try {
            $feeCollection = CollectFee::with([
                'institute:id,name,institute_code,phone', 
                'zamat:id,name'
            ])->findOrFail($id);

            $studentIds = json_decode($feeCollection->student_ids, true);
            $students = Student::whereIn('id', $studentIds)->get(['id', 'name', 'roll_number', 'registration_number']);

            return response()->json([
                'message' => 'Fee collection details retrieved successfully.',
                'data' => [
                    'feeCollection' => $feeCollection,
                    'students' => $students
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve fee collection details.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|in:online,offline',
            'transaction_id' => 'nullable|string',
            'exam_id' => 'required|exists:exams,id',
            'institute_id' => 'required|exists:institutes,id',
            'zamat_id' => 'required|exists:zamats,id',
        ]);
    
        DB::beginTransaction();
    
        try {
            $feeCollection = CollectFee::create([
                'student_ids' => json_encode($request->student_ids),
                'total_amount' => $request->total_amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'exam_id' => $request->exam_id,
                'institute_id' => $request->institute_id,
                'zamat_id' => $request->zamat_id,
            ]);
    
            if ($request->payment_method === 'online') {
                $callback_url = env('FRONTEND_BASE_URL', 'https://tanjim.madrasah.cc') . "/bkash/callback/{$feeCollection->id}";
                $response = $this->createPayment($feeCollection->total_amount, $feeCollection->id, $callback_url);
    
                if (isset($response->bkashURL)) {
                    DB::commit();
                    return response()->json([
                        'message' => 'Payment is in progress. Redirecting to payment gateway...',
                        'response' => $response,
                        'success' => !!($response->bkashURL ?? false),
                        'bkashURL' => $response->bkashURL ?? '#',
                    ], 201);
                }
    
                throw new \Exception("Error creating payment: Payment creation failed. No payment ID received.");
            } else {
                $studentIds = $request->student_ids;
                $this->assignRollNumbers($studentIds, $request->exam_id);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Fee collected successfully and roll numbers assigned.',
                'data' => $feeCollection,
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            return response()->json([
                'message' => 'Failed to collect fee.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function bkashExecutePayment($id, Request $request)
    {
        $paymentID = $request->input('paymentID');
    
        if ($paymentID) {
            $response = $this->executePayment($paymentID);
    
            if ($response && ($response->transactionStatus ?? '') === 'Completed') {
                $feeCollection = CollectFee::findOrFail($id);
                $feeCollection->update([
                    'transaction_id' => $response->trxID,
                    'payment_method' => 'online',
                ]);
    
                $studentIds = json_decode($feeCollection->student_ids, true);
                $this->assignRollNumbers($studentIds, $feeCollection->exam_id);
    
                $institutePhone = $feeCollection->institute->phone ?? null;
                $examName = $feeCollection->exam->name ?? '';
                $zamatName = $feeCollection->zamat->name ?? '';
                $instituteCode = $feeCollection->institute->institute_code ?? '';
                $totalAmount = $feeCollection->total_amount;
                $transactionId = $response->trxID;
                $totalStudent = count($studentIds);
    
                if (!empty($institutePhone)) {
                    $message = "\"{$examName}\"-এর ফি জমা সফল হয়েছে! ইলহাক: {$instituteCode}, মারহালা: {$zamatName}, পরীক্ষার্থী সংখ্যা: {$totalStudent} জন, ফি’র পরিমান: {$totalAmount}TK, TRXID: {$transactionId}\nধন্যবাদ\n-তানযীম";
    
                    $smsResponse = $this->sendSmsWithStore(
                        $message,
                        $institutePhone,
                        "Fee Collection",
                        $feeCollection->institute->id ?? null
                    );
    
                    if ($smsResponse && $smsResponse->failed()) {
                        return response()->json([
                            'message' => 'Payment successful, but SMS sending failed.',
                            'status' => true,
                        ], 201);
                    }
                }
    
                return response()->json([
                    'message' => 'Payment successful. Fee collected and SMS sent.',
                    'status' => true,
                ], 201);
            } else {
                return response()->json([
                    'message' => 'Payment failed. Please try again.',
                    'status' => false,
                ], 200);
            }
        }
    
        return response()->json([
            'message' => 'Invalid payment ID or status.',
            'status' => false,
        ], 200);
    }

    private function assignRollNumbers(array $studentIds, $examId)
    {
        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
    
            if (!$student->roll_number) {
                $previousMaxRollNumber = Student::query()
                    ->where('exam_id', $examId)
                    ->max('roll_number');
    
                $student->roll_number = $previousMaxRollNumber
                    ? $previousMaxRollNumber + 1
                    : $examId . "0001";
                $student->save();
            }
        }
    }
}