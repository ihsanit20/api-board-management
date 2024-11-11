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

    public function store(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'total_amount' => 'required|numeric',
            'payment_method' => 'required|in:online,offline',
            'transaction_id' => 'nullable|string',
        ]);
    
        DB::beginTransaction();
    
        try {
            $feeCollection = CollectFee::create([
                'student_ids' => json_encode($request->student_ids),
                'total_amount' => $request->total_amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
            ]);
    
            if ($request->payment_method === 'online') {
                $callback_url = env('FRONTEND_BASE_URL', 'http://localhost:5173') . "/bkash/callback/{$feeCollection->id}";
                $response = $this->createPayment($feeCollection->total_amount, $feeCollection->id, $callback_url);
    
                if (isset($response->bkashURL)) {
                    DB::commit();
                    return response()->json([
                       'message'   => 'Payment is in progress. Redirecting to payment gateway...',
                        'response'  => $response,
                        'success'   => !!($response->bkashURL ?? false),
                        'bkashURL'  => $response->bkashURL ?? '#'
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
    
            if ($response->transactionStatus === 'Completed') {
                $feeCollection = CollectFee::findOrFail($id);
                $feeCollection->update([
                    'transaction_id' => $response->trxID,
                    'payment_method' => 'online',
                ]);
    
                $studentIds = json_decode($feeCollection->student_ids, true);
                $this->assignRollNumbers($studentIds);
    
                return response()->json([
                    'message' => 'Payment successful. Fee collected.',
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
    
    private function assignRollNumbers(array $studentIds)
    {
        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);

            if (!$student->roll_number) {
                $student->roll_number = str_pad($student->id, 6, '0', STR_PAD_LEFT);
                $student->save();
            }
        }
    }

}
