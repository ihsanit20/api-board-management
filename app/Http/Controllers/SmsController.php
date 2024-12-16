<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SmsLog;
use App\Models\SmsRecord;

class SmsController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

        $smsLogs = SmsRecord::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $smsLogs
        ]);
    }

    public function seeRecords(Request $request)
    {
        $perPage = $request->query('per_page', 10);
        $smsRecords = SmsRecord::orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json($smsRecords, 200);
    }

    public function count()
    {
        $totalSmsParts = SmsRecord::sum('sms_count');

        return response()->json([
            'success' => true,
            'total' => $totalSmsParts,
        ]);
    }


    public function sendSms(Request $request)
    {
        $validated = $request->validate([
            'numbers' => 'required|string',
            'message' => 'required|string',
        ]);

        $number = $validated['numbers'];
        $message = $validated['message'];
        $event = "SMS Panel";

        try {
            $response = $this->sendSmsWithStore($message, $number, $event);

            return response()->json([
                'success' => $response->successful(),
                'message' => $response->successful() ? 'SMS sent successfully' : 'SMS sending failed',
                'response' => $response->body(),
            ], $response->successful() ? 200 : 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
