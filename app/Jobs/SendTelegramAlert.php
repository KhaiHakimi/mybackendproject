<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramAlert implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $chatId,
        public string $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TelegramService $telegram): void
    {
        $telegram->sendMessage($this->chatId, $this->message);
    }
}
