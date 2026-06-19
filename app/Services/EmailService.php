<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected bool $isMock;

    public function __construct()
    {
        $this->isMock = filter_var(env('NOTIFICATION_MOCK', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Send raw email notification.
     *
     * @param string $recipient
     * @param string $subject
     * @param string $body
     * @return void
     */
    public function sendEmail(string $recipient, string $subject, string $body): void
    {
        if ($this->isMock) {
            // Log to php://stdout (Docker console)
            file_put_contents('php://stdout', "\n[EMAIL OUTBOUND MOCK] To: {$recipient} | Subject: {$subject}\nBody:\n{$body}\n----------------------------------------\n");
            
            // Log to laravel.log
            Log::info("[EMAIL OUTBOUND MOCK] To: {$recipient} | Subject: {$subject} | Body: " . str_replace("\n", " ", $body));

            // Push to session to display toast
            try {
                $mockNotifications = session()->get('mock_notifications', []);
                $mockNotifications[] = [
                    'type' => 'email',
                    'to' => $recipient,
                    'subject' => $subject,
                    'message' => $body
                ];
                session()->put('mock_notifications', $mockNotifications);
            } catch (\Exception $e) {
                // Ignore session exceptions (e.g. during CLI commands)
            }
        } else {
            try {
                Log::info("Sending real email to {$recipient} with subject: {$subject}");
                Mail::raw($body, function ($message) use ($recipient, $subject) {
                    $message->to($recipient)->subject($subject);
                });
            } catch (\Exception $e) {
                Log::error("Failed to send real email: " . $e->getMessage());
            }
        }
    }
}
