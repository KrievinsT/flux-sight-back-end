<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Exception;

class TwilioSMSController extends Controller
{
    public function index(Request $request)
    {
        try {  

            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');
            $from = env('TWILIO_FROM');
            $twilio = new Client($sid, $token);

            $recipient = $request->input('to');
            
            // Validate the phone number format
            if (!preg_match('/^\+[1-9]\d{1,14}$/', $recipient)) {
                return response()->json(['error' => 'Invalid phone number format.'], 400);
            }

            $message = $twilio->messages->create(
                $recipient,
                [
                    'from' => $from,
                    'body' => 'Hello, this is a test message from Twilio!'
                ]
            );

            return response()->json(['message' => 'SMS sent successfully to ' . $recipient]);
        } catch (Exception $e) {
            \Log::error('Error sending SMS: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to send SMS.'], 500);
        }
    }
}
?>
