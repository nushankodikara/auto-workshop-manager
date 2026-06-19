<?php

namespace App\Jobs;

use App\Services\FitSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendSmsJob implements ShouldQueue
{
    use Queueable;

    protected string $recipient;
    protected string $message;

    /**
     * Create a new job instance.
     */
    public function __construct(string $recipient, string $message)
    {
        $this->recipient = $recipient;
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(FitSmsService $smsService): void
    {
        $smsService->sendSmsDirect($this->recipient, $this->message);
    }
}
