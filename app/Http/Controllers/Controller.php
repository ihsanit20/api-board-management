<?php

namespace App\Http\Controllers;

use App\Models\SmsRecord;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function sendSmsWithStore($message, $number, $event, $institute_id = null)
    {
        $price_per_sms = 0.5;

        try {
            $response = Http::post(env('SMS_API_URL'), [
                'api_key'   => env('SMS_API_KEY'),
                'senderid'  => env('SMS_SENDER_ID'),
                'number'    => $number,
                'message'   => $message,
                'type'      => 'text'
            ]);

            $status = $response->successful() ? 'sent' : 'failed';

            $sms_parts = $this->countSmsLength($message);

            $numbers = explode(',', $number);

            SmsRecord::create([
                'message'       => $message,
                'sms_parts'     => $sms_parts,
                'sms_count'     => count($numbers) * $sms_parts,
                'numbers'       => $numbers,
                'cost'          => count($numbers) * $sms_parts * $price_per_sms,
                'event'         => $event,
                'status'        => $status,
                'institute_id'  => $institute_id,
            ]);

            return $response;

        } catch (\Exception $e) {
            return false;
        }
    }

    function countSmsLength($message) {
        $messageLength = mb_strlen($message, 'UTF-8');

        if (preg_match('/^[\x00-\x7F]*$/', $message))
        {
            $sms_count = $messageLength <= 160
                ? 1
                : ceil($messageLength / 153);
        }
        else
        {
            $sms_count = $messageLength <= 70
                ? 1
                : ceil($messageLength / 67);
        }

        return $sms_count;
    }

}
