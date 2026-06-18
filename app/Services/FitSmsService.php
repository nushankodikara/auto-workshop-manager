<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FitSmsService
{
    protected string $apiToken;
    protected string $senderId;
    protected string $apiUrl = 'https://app.fitsms.lk/api/v4/sms/send';

    public function __construct()
    {
        $this->apiToken = env('FITSMS_API_TOKEN', '');
        $this->senderId = env('FITSMS_SENDER_ID', 'TDC');
    }

    /**
     * Send outbound SMS.
     *
     * @param string $recipient Phone number (e.g., +94771234567 or 94771234567)
     * @param string $message The body of the SMS message
     * @return array
     */
    public function sendSms(string $recipient, string $message): array
    {
        // Sanitize recipient: FitSMS expects country code format without +
        $cleanRecipient = ltrim($recipient, '+');

        if (empty($this->apiToken)) {
            Log::warning("FitSMS Alert: API token is not configured. SMS log: [To: {$cleanRecipient}] Message: {$message}");
            return [
                'status' => 'mocked',
                'message' => 'SMS not sent: API token is missing. Logged to Laravel log.'
            ];
        }

        try {
            Log::info("Sending FitSMS request to {$cleanRecipient} via sender {$this->senderId}");

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiToken}",
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'recipient' => $cleanRecipient,
                'sender_id' => $this->senderId,
                'type' => 'plain',
                'message' => $message,
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info("FitSMS Success response:", $responseData);
                return $responseData;
            }

            Log::error("FitSMS API error status: {$response->status()} - Response: " . $response->body());
            return [
                'status' => 'error',
                'message' => $response->json('message') ?? 'API request failed.'
            ];

        } catch (\Exception $e) {
            Log::error("FitSMS Exception: " . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
