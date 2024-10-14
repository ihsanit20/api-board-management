<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SmsLog; 

class SmsController extends Controller
{

    public function index(Request $request)
    {
        // Optional pagination size from query, default is 10
        $perPage = $request->query('per_page', 10);

        // Fetch paginated SMS logs, sorted by latest
        $smsLogs = SmsLog::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $smsLogs
        ]);
    }

    public function count()
    {
        $count = SmsLog::count(); // Count total SMS logs

        return response()->json([
            'success' => true,
            'total' => $count,
        ]);
    }

    public function sendSms(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
        ]);

        $number = $request->input('number');
        $message = $request->input('message');

        try {
            $response = Http::get(env('SMS_API_URL'), [
                'api_key'   => env('SMS_API_KEY'),
                'senderid'  => env('SMS_SENDER_ID'),
                'number'    => $number,
                'message'   => $message,
                'type'      => 'text'
            ]);

            if ($response->successful()) {
                SmsLog::create([
                    'institute_name' => $request->input('institute_name') ?? 'Unknown',
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'sent', 
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->body()
                ]);
            } else {
                SmsLog::create([
                    'institute_name' => $request->input('institute_name') ?? 'Unknown',
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'failed',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'SMS sending failed',
                    'response' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            SmsLog::create([
                'institute_name' => $request->input('institute_name') ?? 'Unknown',
                'phone_number'   => $number,
                'message'        => $message,
                'status'         => 'error',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
