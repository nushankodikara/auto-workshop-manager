<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    protected string $recipient;
    protected string $subject;
    protected string $body;

    /**
     * Create a new job instance.
     */
    public function __construct(string $recipient, string $subject, string $body)
    {
        $this->recipient = $recipient;
        $this->subject = $subject;
        $this->body = $body;
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        $emailService->sendEmailDirect($this->recipient, $this->subject, $this->body);
    }
}
