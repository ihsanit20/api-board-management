<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SmsLog; 

class SmsController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10);

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
        // Validate all required inputs
        $request->validate([
            'number'         => 'required|string',
            'message'        => 'required|string',
            'institute_code' => 'nullable|string', // Validate institute_code (can be nullable)
            'institute_name' => 'nullable|string', // Validate institute_name (can be nullable)
        ]);

        // Get the validated inputs
        $number = $request->input('number');
        $message = $request->input('message');
        $institute_code = $request->input('institute_code') ?? 'Unknown'; // Set default if not provided
        $institute_name = $request->input('institute_name') ?? 'Unknown'; // Set default if not provided

        try {
            // Make the API request to the SMS service
            $response = Http::get(env('SMS_API_URL'), [
                'api_key'   => env('SMS_API_KEY'),
                'senderid'  => env('SMS_SENDER_ID'),
                'number'    => $number,
                'message'   => $message,
                'type'      => 'text'
            ]);

            if ($response->successful()) {
                // Log SMS data to the database as 'sent'
                SmsLog::create([
                    'institute_code' => $institute_code,
                    'institute_name' => $institute_name,
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'sent',
                ]);

                // Return success response
                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->body()
                ]);
            } else {
                // Log SMS data as 'failed'
                SmsLog::create([
                    'institute_code' => $institute_code,
                    'institute_name' => $institute_name,
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'failed',
                ]);

                // Return failure response
                return response()->json([
                    'success' => false,
                    'message' => 'SMS sending failed',
                    'response' => $response->body()
                ], 500);
            }
        } catch (\Exception $e) {
            // Log the SMS data as 'error' when an exception occurs
            SmsLog::create([
                'institute_code' => $institute_code,
                'institute_name' => $institute_name,
                'phone_number'   => $number,
                'message'        => $message,
                'status'         => 'error',
            ]);

            // Return error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

}
