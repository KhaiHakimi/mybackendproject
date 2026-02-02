<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;

    protected $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}/";
    }

    public function sendMessage($chatId, $text, $replyMarkup = null)
    {
        if (! $this->botToken || ! $chatId) {
            Log::warning('Telegram Bot Token or Chat ID missing.');

            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::post($this->baseUrl.'sendMessage', $payload);

            if ($response->successful()) {
                return $response->json()['result'] ?? true;
            } else {
                Log::error('Telegram Send Error: '.$response->body());

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telegram Exception: '.$e->getMessage());

            return false;
        }
    }

    public function editMessageText($chatId, $messageId, $text, $replyMarkup = null)
    {
        try {
            $payload = [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ];

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            Http::post($this->baseUrl.'editMessageText', $payload);
            return true;
        } catch (\Exception $e) {
            Log::error('Telegram Edit Exception: '.$e->getMessage());
            return false;
        }
    }

    public function getUpdates($offset = 0)
    {
        if (! $this->botToken) {
            return [];
        }

        try {
            $response = Http::get($this->baseUrl.'getUpdates', [
                'offset' => $offset,
                'timeout' => 30, // Long polling
            ]);

            if ($response->successful()) {
                return $response->json()['result'] ?? [];
            }
        } catch (\Exception $e) {
            Log::error('Telegram GetUpdates Exception: '.$e->getMessage());
        }

        return [];
    }
}
