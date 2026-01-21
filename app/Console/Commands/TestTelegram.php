<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TestTelegram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:test {--message= : Optional custom message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test message to the configured Telegram Chat ID';

    /**
     * Execute the console command.
     */
    public function handle(TelegramService $telegram)
    {
        $chatId = config('services.telegram.chat_id');

        if (! $chatId) {
            $this->error('TELEGRAM_CHAT_ID is not set in your .env file.');

            return;
        }

        $message = $this->option('message') ?: "👋 *Hello from FerryCast!* \n\nThis is a test message to confirm your Telegram Bot is correctly integrated.\n\n✅ System Status: **ONLINE**";

        $this->info("Sending message to Chat ID: $chatId...");

        $success = $telegram->sendMessage($chatId, $message);

        if ($success) {
            $this->info('✅ Message sent successfully! Check your Telegram.');
        } else {
            $this->error('❌ Failed to send message. Check the logs/terminal output for details.');
            $this->warn('Possible reasons:');
            $this->warn('1. Bot Token is incorrect.');
            $this->warn('2. Chat ID is incorrect.');
            $this->warn('3. You haven\'t started a conversation with the bot (click "Start" in Telegram).');
        }
    }
}
