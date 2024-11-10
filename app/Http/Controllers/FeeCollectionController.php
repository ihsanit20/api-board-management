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

    /**
     * Store a new fee collection record and initiate payment.
     */
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
            // Fee Collection Record তৈরি করা
            $feeCollection = CollectFee::create([
                'student_ids' => json_encode($request->student_ids),
                'total_amount' => $request->total_amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
            ]);

            // অনলাইন পেমেন্ট হলে, বিকাশ পেমেন্ট ইন্টিগ্রেশন শুরু করা
            if ($request->payment_method === 'online') {
                $callback_url = env('FRONTEND_BASE_URL') . "/bkash/callback/{$feeCollection->id}";
                $response = $this->createPayment($feeCollection->total_amount, $feeCollection->id, $callback_url);

                DB::commit();

                return response()->json([
                    'message' => 'Redirecting to bKash payment gateway...',
                    'bkashURL' => $response->bkashURL ?? '#',
                    'success' => true,
                ]);
            }

            // অফলাইন পেমেন্ট হলে শিক্ষার্থীদের রোল নম্বর অ্যাসাইন করা
            $this->assignRollNumbers($request->student_ids);

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

    /**
     * bKash Payment Execution
     */
    public function bkashExecutePayment($id, Request $request)
    {
        $paymentID = $request->input('paymentID');
        $feeCollection = CollectFee::findOrFail($id);

        if ($paymentID) {
            $response = $this->executePayment($paymentID);

            if ($response->transactionStatus === 'Completed') {
                $feeCollection->update([
                    'transaction_id' => $response->trxID,
                    'payment_method' => 'online',
                ]);

                // শিক্ষার্থীদের রোল নম্বর অ্যাসাইন করা
                $studentIds = json_decode($feeCollection->student_ids, true);
                $this->assignRollNumbers($studentIds);

                return response()->json([
                    'message' => 'Payment successful. Fee collected.',
                    'status' => true,
                ]);
            } else {
                return response()->json([
                    'message' => 'Payment failed. Please try again.',
                    'status' => false,
                ]);
            }
        }

        return response()->json([
            'message' => 'Invalid payment ID.',
            'status' => false,
        ]);
    }

    /**
     * Assign roll numbers to students
     */
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
