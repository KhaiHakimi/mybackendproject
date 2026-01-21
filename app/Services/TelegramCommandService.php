<?php

namespace App\Services;

use App\Models\Port;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TelegramCommandService
{
    protected $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle($message)
    {
        $chatId = $message['chat']['id'];

        // Handle Contact Sharing (Account Linking)
        if (isset($message['contact'])) {
            $this->handleContact($chatId, $message['contact']);

            return;
        }

        $text = $message['text'] ?? '';
        $username = $message['from']['first_name'] ?? 'User';

        Log::info("Telegram Command from $username ($chatId): $text");

        $parts = explode(' ', trim($text));
        $command = strtolower($parts[0]);
        $args = array_slice($parts, 1);

        switch ($command) {
            case '/start':
                $payload = $args[0] ?? null; // Capture Deep Link Code
                $this->sendWelcome($chatId, $username, $payload);
                break;
            case '/help':
                $this->sendHelp($chatId);
                break;
            case '/weather':
                $this->handleWeather($chatId, $args);
                break;
            case '/schedule':
                $this->handleSchedule($chatId, $args);
                break;
            case '/server':
                $this->handleServer($chatId);
                break;
            default:
                $this->telegram->sendMessage($chatId, "I don't recognize that command. Try /help to see what I can do.");
        }
    }

    protected function sendWelcome($chatId, $username, $payload = null)
    {
        // 1. Check Payload (Deep Linking)
        if ($payload) {
            $user = User::where('telegram_verification_code', $payload)->first();

            if ($user) {
                // Link Account
                $user->update([
                    'telegram_chat_id' => $chatId,
                    'telegram_verification_code' => null, // Consumed
                ]);

                $msg = "✅ <b>Success! Account Linked</b>\n\n".
                       "Hello <b>{$user->name}</b>,\n".
                       "Your Telegram account has been automatically linked to your registered phone number: <code>{$user->phone_number}</code>.\n\n".
                       'You will now receive High Risk alerts here directly.';

                $this->telegram->sendMessage($chatId, $msg);

                return;
            } else {
                $this->telegram->sendMessage($chatId, "⚠️ <b>Invalid or Expired Link</b>\nPlease try connecting from the website again.");

                return;
            }
        }

        // 2. Standard Start (No Code)
        // Check if user is already linked
        $isLinked = User::where('telegram_chat_id', $chatId)->exists();

        if ($isLinked) {
            $message = "👋 <b>Welcome back, $username!</b>\n\n".
                "Your account is successfully linked ✅.\n".
                "You will receive automatic alerts here.\n\n".
                'Type /help to see available commands.';

            $this->telegram->sendMessage($chatId, $message, ['remove_keyboard' => true]);
        } else {
            $message = "👋 <b>Hello, $username!</b>\n\n".
                "I am the <b>FerryCast Bot</b>.\n\n".
                "⚠️ <b>Action Required:</b>\n".
                "To receive risk notifications, I need to know who you are.\n".
                'Please click the button below to share your phone number.';

            $keyboard = [
                'keyboard' => [[
                    ['text' => '📱 Share My Phone Number', 'request_contact' => true],
                ]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ];

            $this->telegram->sendMessage($chatId, $message, $keyboard);
        }
    }

    protected function handleContact($chatId, $contact)
    {
        $phone = $contact['phone_number'];

        // Strict Validation: Malaysian Numbers Only
        // Telegram usually sends E.164 (e.g. 60123456789)
        // We ensure it starts with 60 or +60
        $isMalaysian = str_starts_with($phone, '60') || str_starts_with($phone, '+60');

        if (! $isMalaysian) {
            $msg = "⛔️ <b>Restricted Access</b>\n\n".
                   'Sorry, this service is currently available for <b>Malaysian phone numbers (+60)</b> only.';
            $this->telegram->sendMessage($chatId, $msg, ['remove_keyboard' => true]);

            return;
        }

        // 1. Clean format (remove +)
        $cleanPhone = ltrim($phone, '+');

        // 2. Try common formats
        // If clean starts with 60, try replacing with 0
        $localFormat = $cleanPhone;
        if (str_starts_with($cleanPhone, '60')) {
            $localFormat = '0'.substr($cleanPhone, 2);
        }

        $user = User::where('phone_number', $phone)
            ->orWhere('phone_number', $cleanPhone)
            ->orWhere('phone_number', '+'.$cleanPhone)
            ->orWhere('phone_number', $localFormat)
            ->first();

        if ($user) {
            $user->update([
                'telegram_chat_id' => $chatId,
                // We could also store username if needed: 'telegram_username' => ...
            ]);

            $msg = "✅ <b>Account Linked!</b>\n\n".
                   "Thanks, <b>{$user->name}</b>. You are now set up to receive High Risk alerts directly here.";

            $this->telegram->sendMessage($chatId, $msg, ['remove_keyboard' => true]);
        } else {
            $msg = "❌ <b>Account Not Found</b>\n\n".
                   "We could not find a registered account with the number: <code>$phone</code>.\n".
                   'Please register on the website first.';

            $this->telegram->sendMessage($chatId, $msg, ['remove_keyboard' => true]);
        }
    }

    protected function sendHelp($chatId)
    {
        $message = "<b>🤖 Available Commands:</b>\n\n".
            "🌥 <b>/weather [Port Name]</b>\n".
            "<i>(e.g., /weather Kuala Perlis)</i>\n".
            "Check current weather & risk status.\n\n".
            "🚢 <b>/schedule [From] [To]</b>\n".
            "<i>(e.g., /schedule Langkawi Kuala Kedah)</i>\n".
            "Check next upcoming ferry.\n\n".
            "⚙️ <b>/server</b>\n".
            'Check system health (Admin only).';

        $this->telegram->sendMessage($chatId, $message);
    }

    protected function handleWeather($chatId, $args)
    {
        if (empty($args)) {
            $this->telegram->sendMessage($chatId, "⚠️ Please provide a port name.\nExample: <code>/weather Kuala Perlis</code>");

            return;
        }

        $query = implode(' ', $args);
        $port = Port::where('name', 'LIKE', "%{$query}%")->first();

        if (! $port) {
            $this->telegram->sendMessage($chatId, "❌ Port '<b>$query</b>' not found.");

            return;
        }

        $weather = $port->weatherData()->latest()->first();

        if (! $weather) {
            $this->telegram->sendMessage($chatId, "ℹ️ No weather data available for <b>{$port->name}</b> yet.");

            return;
        }

        $emoji = $weather->risk_status === 'High Risk' ? '🔴' : ($weather->risk_status === 'Caution' ? '🟡' : '🟢');

        $message = "<b>🌥 Weather Report: {$port->name}</b>\n\n".
            "<b>Status:</b> $emoji <b>{$weather->risk_status}</b>\n".
            "<b>Risk Score:</b> {$weather->risk_score}%\n\n".
            "💨 <b>Wind:</b> {$weather->wind_speed} km/h\n".
            "🌊 <b>Waves:</b> {$weather->wave_height} m\n".
            "🌧 <b>Rain:</b> {$weather->precipitation} mm\n".
            "👁 <b>Visibility:</b> {$weather->visibility} km\n\n".
            '<i>Last Updated: '.$weather->created_at->diffForHumans().'</i>';

        $this->telegram->sendMessage($chatId, $message);
    }

    protected function handleSchedule($chatId, $args)
    {
        if (count($args) < 2) {
            // Since parsing "From Port To Port" is hard with spaces, let's try a simpler approach or guide them.
            // But for now, let's assume single word names or specific logic.
            // Better: ASK them step by step? No, stateless is better for v1.
            // Let's assume standard format: "Start" "End"
            $this->telegram->sendMessage($chatId, "⚠️ Usage: <code>/schedule [Origin] [Destination]</code>\nExample: <code>/schedule Langkawi Kuala</code>");

            return;
        }

        // Simple heuristic: First half args = From, Second half = To
        // Or just search widely.
        // Let's take first arg as From, second as To for simplicity for now.
        // Or better: $from = $args[0]; $to = $args[1];

        $fromQuery = $args[0];
        $toQuery = $args[1]; // Crude but works for single word names like "Langkawi" "Kuala"

        $fromPort = Port::where('name', 'LIKE', "%{$fromQuery}%")->first();
        $toPort = Port::where('name', 'LIKE', "%{$toQuery}%")->first();

        if (! $fromPort || ! $toPort) {
            $this->telegram->sendMessage($chatId, '❌ Could not find one or both ports.');

            return;
        }

        // Find next departure from NOW
        $schedule = Schedule::where('origin_port_id', $fromPort->id)
            ->where('destination_port_id', $toPort->id)
            ->where('departure_time', '>=', now())
            ->orderBy('departure_time', 'asc')
            ->with('ferry')
            ->first();

        if (! $schedule) {
            $this->telegram->sendMessage($chatId, "❌ No upcoming trips found today from <b>{$fromPort->name}</b> to <b>{$toPort->name}</b>.");

            return;
        }

        $time = \Carbon\Carbon::parse($schedule->departure_time)->format('h:i A');
        $date = \Carbon\Carbon::parse($schedule->departure_time)->format('d M Y');

        $message = "<b>🚢 Next Ferry Departure</b>\n\n".
            "📍 <b>Route:</b> {$fromPort->name} ➡️ {$toPort->name}\n".
            "🕐 <b>Time:</b> $time\n".
            "📅 <b>Date:</b> $date\n".
            "⛴ <b>Ferry:</b> {$schedule->ferry->name}\n".
            "💵 <b>Price:</b> RM {$schedule->price}";

        $this->telegram->sendMessage($chatId, $message);
    }

    protected function handleServer($chatId)
    {
        // Security check: Only allow the configured Admin ID
        if ($chatId != config('services.telegram.chat_id')) {
            $this->telegram->sendMessage($chatId, '⛔️ Unauthorized. This command is for admins only.');

            return;
        }

        $dbStatus = 'Unknown';
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $dbStatus = '🟢 Online';
        } catch (\Exception $e) {
            $dbStatus = '🔴 Offline';
        }

        $memory = round(memory_get_usage() / 1024 / 1024, 2);

        $message = "<b>⚙️ System Status</b>\n\n".
            "🖥 <b>Laravel:</b> 🟢 Running\n".
            "🗄 <b>Database:</b> $dbStatus\n".
            "🧠 <b>Memory:</b> {$memory} MB\n".
            '🕒 <b>Server Time:</b> '.now()->toDateTimeString();

        $this->telegram->sendMessage($chatId, $message);
    }
}
