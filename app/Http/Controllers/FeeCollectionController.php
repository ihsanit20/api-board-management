<?php

namespace App\Http\Controllers;

use App\Models\CollectFee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Msilabs\Bkash\BkashPayment;

class FeeCollectionController extends Controller
{
    use BkashPayment;
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
                $callback_url = env('FRONTEND_BASE_URL', 'http://localhost:5173') . "/bkash/callback/{$feeCollection->id}";
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
            }

            DB::commit();

            return response()->json([
                'message' => 'Fee collected successfully.',
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
                    $message = "আপনার \"{$examName}\"-এর ফি জমা সফল হয়েছে! ইলহাক: {$instituteCode}, মারহালা: {$zamatName}, পরীক্ষার্থী সংখ্যা: {$totalStudent}, ফি’র পরিমান: {$totalAmount}, Trx id: {$transactionId}\nধন্যবাদ\n-তানযীম";
    
                    $smsResponse = Http::get(env('SMS_API_URL'), [
                        'api_key'   => env('SMS_API_KEY'),
                        'senderid'  => env('SMS_SENDER_ID'),
                        'number'    => $institutePhone,
                        'message'   => $message,
                        'type'      => 'text'
                    ]);
    
                    if ($smsResponse->failed()) {
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
