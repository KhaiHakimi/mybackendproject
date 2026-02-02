<?php

namespace App\Console\Commands;

use App\Services\TelegramCommandService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramUpdates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the Telegram Bot Listener (Long Polling)';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram, TelegramCommandService $commandService)
    {
        $this->info('🚀 FerryCast Bot Started!');
        $this->info('Press Ctrl+C to stop.');

        $offset = 0;

        while (true) {
            try {
                // Get updates with timeout (Long Polling)
                $result = $telegram->getUpdates($offset);

                foreach ($result as $update) {
                    $offset = $update['update_id'] + 1;

                    if (isset($update['callback_query'])) {
                        $commandService->handleCallback($update['callback_query']);
                    } elseif (isset($update['message'])) {
                        // Pass the message to our new Command Service
                        $commandService->handle($update['message']);
                    }
                }
            } catch (\Exception $e) {
                // Prevent crash on network error, just log and retry
                $this->error('Error: '.$e->getMessage());
                Log::error('Telegram Loop Error: '.$e->getMessage());
                sleep(5); // Wait before retrying
            }

            // Small Sleep not strictly needed if long polling timeout is used in Service,
            // but good safety if getUpdates returns immediately.
            usleep(500000); // 0.5s
        }
    }
}
