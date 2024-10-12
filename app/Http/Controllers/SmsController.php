<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\SmsLog; // Assuming you have created the SMS log model

class SmsController extends Controller
{
    public function sendSms(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string',
        ]);

        $number = $request->input('number');
        $message = $request->input('message');

        try {
            // Prepare the SMS API request
            $response = Http::get(env('BULKSMS_API_URL'), [
                'api_key'   => env('BULKSMS_API_KEY'),
                'senderid'  => env('BULKSMS_SENDER_ID'),
                'number'    => $number,
                'message'   => $message,
                'type'      => 'text'
            ]);

            // Check response and log the result
            if ($response->successful()) {
                // Log the SMS sending details into the database
                SmsLog::create([
                    'institute_name' => $request->input('institute_name') ?? 'Unknown', // Optional field
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'sent',  // Set status to sent
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'response' => $response->body()  // Return API response if needed
                ]);
            } else {
                // Log the failure into the database
                SmsLog::create([
                    'institute_name' => $request->input('institute_name') ?? 'Unknown',
                    'phone_number'   => $number,
                    'message'        => $message,
                    'status'         => 'failed', // Set status to failed
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'SMS sending failed',
                    'response' => $response->body()  // Return API response for failure analysis
                ], 500);
            }
        } catch (\Exception $e) {
            // Catch and log any error that occurs during the process
            SmsLog::create([
                'institute_name' => $request->input('institute_name') ?? 'Unknown',
                'phone_number'   => $number,
                'message'        => $message,
                'status'         => 'error', // Error status
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending SMS',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
