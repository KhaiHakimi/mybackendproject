<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Help obtain the Chat ID for Telegram integration';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram)
    {
        $this->info('Telegram Bot Integration Setup');

        $token = env('TELEGRAM_BOT_TOKEN');
        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN is missing in .env file.');

            return;
        }

        $this->info('Listening for messages to Bot... (Token: '.substr($token, 0, 10).'...)');
        $this->comment("Please send a message 'Hello' to your bot on Telegram now.");

        $maxAttempts = 1;
        $chatId = null;

        $confirm = $this->confirm('Have you sent a message to the bot yet?', true);

        if ($confirm) {
            $updates = $telegram->getUpdates();

            if (isset($updates['result']) && count($updates['result']) > 0) {
                // Get the last message
                $lastUpdate = end($updates['result']);
                if (isset($lastUpdate['message']['chat']['id'])) {
                    $chatId = $lastUpdate['message']['chat']['id'];
                    $name = $lastUpdate['message']['chat']['first_name'] ?? 'User';

                    $this->info("Success! Found Chat ID: $chatId (From: $name)");
                    $this->line('Add the following line to your .env file:');
                    $this->info("TELEGRAM_CHAT_ID=$chatId");
                } else {
                    $this->warn('Could not extract Chat ID from the last update.');
                    dump($lastUpdate);
                }
            } else {
                $this->warn('No updates found. Make sure you messaged the correct bot.');
            }
        }
    }
}
