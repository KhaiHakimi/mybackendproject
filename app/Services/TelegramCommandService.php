<?php

namespace App\Services;

use App\Models\Port;
use App\Models\Schedule;
use App\Models\User;
use App\Models\RouteSubscription;
use App\Services\WeatherService;
use App\Services\GeoIntelligenceService;
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
            case '/notify':
                $this->handleNotify($chatId, $args);
                break;
            case '/server':
                $this->handleServer($chatId);
                break;
            default:
                // INTELLIGENT BOT: Use NLP for natural conversation instead of hard-fail
                $this->handleNaturalText($chatId, $text);
        }
    }

    public function handleCallback($callback)
    {
        $chatId = $callback['message']['chat']['id'];
        $data = $callback['data'];
        
        Log::info("Telegram Callback from ($chatId): $data");

        if (str_starts_with($data, 'w_')) {
            // Weather: w_{port_id}
            $portId = str_replace('w_', '', $data);
            $port = Port::find($portId);
            if ($port) $this->sendWeatherReport($chatId, $port);
        }
        elseif (str_starts_with($data, 's_o_')) {
            // Schedule Origin: s_o_{port_id}
            $originId = str_replace('s_o_', '', $data);
            $this->askForScheduleDestination($chatId, $originId);
        }
        elseif (str_starts_with($data, 's_d_')) {
            // Schedule Dest: s_d_{origin}_{dest}
            $parts = explode('_', $data);
            // 0=s, 1=d, 2=origin, 3=dest
            if (count($parts) >= 4) {
                 $originId = $parts[2];
                 $destId = $parts[3];
                 $from = Port::find($originId);
                 $to = Port::find($destId);
                 if ($from && $to) $this->sendScheduleReport($chatId, $from, $to);
            }
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
            "🔔 <b>/notify [From] [To]</b>\n".
            "<i>(e.g., /notify Langkawi Kuala Kedah)</i>\n".
            "Get alerted when safe to travel.\n\n".
            "⚙️ <b>/server</b>\n".
            'Check system health (Admin only).';

        $this->telegram->sendMessage($chatId, $message);
    }

    protected function handleWeather($chatId, $args)
    {
        if (empty($args)) {
            $keyboard = $this->getPortsKeyboard('w_');
            $this->telegram->sendMessage($chatId, "📍 <b>Check Weather</b>\nSelect a jetty:", ['inline_keyboard' => $keyboard]);
            return;
        }

        $query = implode(' ', $args);
        $port = Port::where('name', 'LIKE', "%{$query}%")->first();

        if (! $port) {
            $this->telegram->sendMessage($chatId, "❌ Port '<b>$query</b>' not found.");
            return;
        }

        $this->sendWeatherReport($chatId, $port);
    }

    protected function sendWeatherReport($chatId, $port)
    {
        // 1. Send "Thinking/Analyzing" placeholder and Capture ID
        $sentMessage = $this->telegram->sendMessage($chatId, "🤖 <b>FerryCast AI</b> is analyzing satellite data for <b>{$port->name}</b>... Please wait.");
        $messageId = is_array($sentMessage) ? ($sentMessage['message_id'] ?? null) : null;

        // 2. Fetch REAL-TIME data
        $weather = app(WeatherService::class)->updateWeatherForPort($port);

        if (! $weather) {
           // Show last known if fail
           $weather = $port->weatherData()->latest()->first();
           $msg = "ℹ️ Real-Time Unavailable. Showing last known record.";
        } else {
             $msg = "";
        }

        if (! $weather) {
            $errorMsg = "❌ No weather data available at all.";
            if ($messageId) {
                $this->telegram->editMessageText($chatId, $messageId, $errorMsg);
            } else {
                $this->telegram->sendMessage($chatId, $errorMsg);
            }
            return;
        }

        $emoji = $weather->risk_status === 'High Risk' ? '🔴' : ($weather->risk_status === 'Caution' ? '🟡' : '🟢');
        
        $finalText = "<b>🌥 Real-Time Weather Report: {$port->name}</b>\n\n".
            "<b>Status:</b> $emoji <b>{$weather->risk_status}</b>\n".
            "<b>Risk Score:</b> {$weather->risk_score}%\n\n".
            "💨 <b>Wind:</b> {$weather->wind_speed} km/h\n".
            "🌊 <b>Waves:</b> {$weather->wave_height} m\n".
            "🌧 <b>Rain:</b> {$weather->precipitation} mm\n".
            "👁 <b>Visibility:</b> {$weather->visibility} km\n\n".
            '<i>Analysis Time: '.now()->toDateTimeString().'</i>';
            
        if ($msg) $finalText = $msg . "\n\n" . $finalText;

        // Check if we can edit
        if ($messageId) {
            $this->telegram->editMessageText($chatId, $messageId, $finalText);
        } else {
            $this->telegram->sendMessage($chatId, $finalText);
        }
    }

    protected function handleSchedule($chatId, $args)
    {
        if (count($args) < 2) {
             // Show Origin Buttons
             $keyboard = $this->getPortsKeyboard('s_o_');
             $this->telegram->sendMessage($chatId, "🚢 <b>Find Schedule</b>\nStep 1: Select <b>Origin</b> Jetty:", ['inline_keyboard' => $keyboard]);
             return;
        }

        // ... existing text processing ...
        $fromQuery = $args[0];
        $toQuery = $args[1];

        $fromPort = Port::where('name', 'LIKE', "%{$fromQuery}%")->first();
        $toPort = Port::where('name', 'LIKE', "%{$toQuery}%")->first();

        if (! $fromPort || ! $toPort) {
            $this->telegram->sendMessage($chatId, '❌ Could not find one or both ports.');
            return;
        }

        $this->sendScheduleReport($chatId, $fromPort, $toPort);
    }

    protected function askForScheduleDestination($chatId, $originId)
    {
        $origin = Port::find($originId);
        if (!$origin) return;

        // Find ports that actually have scheduled trips from this origin
        $availableDestIds = Schedule::where('origin_port_id', $originId)
            ->distinct()
            ->pluck('destination_port_id');

        $availableDestinations = Port::whereIn('id', $availableDestIds)->get();

        if ($availableDestinations->isEmpty()) {
            $this->telegram->sendMessage($chatId, "⚠️ Sorry, there are no scheduled ferries departing from <b>{$origin->name}</b> right now.");
            return;
        }

        // Generate buttons for available destinations only
        $buttons = [];
        $row = [];
        foreach ($availableDestinations as $port) {
            $row[] = ['text' => $port->name, 'callback_data' => "s_d_{$originId}_" . $port->id];
            if (count($row) == 2) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) $buttons[] = $row;
        
        $this->telegram->sendMessage($chatId, "🚢 <b>Find Schedule</b>\nStep 2: Start <b>{$origin->name}</b> \nSelect <b>Destination</b>:", ['inline_keyboard' => $buttons]);
    }

    protected function sendScheduleReport($chatId, $fromPort, $toPort)
    {
        // 1. Send "Thinking/Scanning Route" placeholder
        $sentMessage = $this->telegram->sendMessage($chatId, "🛰 <b>FerryCast Geospatial Engine</b> is scanning the full route path from <b>{$fromPort->name}</b> to <b>{$toPort->name}</b>... Please wait.");
        $messageId = is_array($sentMessage) ? ($sentMessage['message_id'] ?? null) : null;

        // 2. Perform Advanced Route Viability Analysis (Waypoints + AI)
        $routeAnalysis = app(GeoIntelligenceService::class)->analyzeRouteViability($fromPort, $toPort);
        
        // 3. Get Origin/Dest Weather for display
        $fromWeather = $fromPort->weatherData()->latest()->first();
        $toWeather = $toPort->weatherData()->latest()->first();
        
        $adviceMsg = "";
        $isRisky = false;

        // A. Check Ports
        if ($fromWeather && $fromWeather->risk_status === 'High Risk') {
             $adviceMsg .= "⚠️ <b>PORT ALERT:</b> High waves at Origin ({$fromPort->name}). Risk Score: {$fromWeather->risk_score}%.\n";
             $isRisky = true;
        }
        if ($toWeather && $toWeather->risk_status === 'High Risk') {
             $adviceMsg .= "⚠️ <b>PORT ALERT:</b> Rough seas at Destination ({$toPort->name}). Risk Score: {$toWeather->risk_score}%.\n";
             $isRisky = true;
        }

        // B. Check Mid-Sea (The New Feature)
        if ($routeAnalysis['is_deep_sea_risky']) {
             $isRisky = true;
             $maxWave = $routeAnalysis['max_wave_height'];
             $adviceMsg .= "🌊 <b>DEEP SEA ALERT:</b> Dangerous swells detected in open water (Max: {$maxWave}m). Even if ports are safe, the journey is risky.\n";
        }

        if ($isRisky) {
            $adviceMsg .= "🔴 <b>Recommendation:</b> It is NOT SAFE to travel right now.\n\n";
            
            // INTELLIGENCE V2: Port Hopper (Alternative Suggestion)
            // If origin is risky, check nearby ports (within 50km) that are SAFE
            $altPort = $this->findAlternativeSafePort($fromPort);
            if ($altPort) {
                $distance = $this->calculateDistance($fromPort->latitude, $fromPort->longitude, $altPort->latitude, $altPort->longitude);
                $adviceMsg .= "💡 <b>SMART TIP:</b> Consider departing from <b>{$altPort->name}</b> instead.\n".
                              "Permission to reroute? It is only <b>{$distance} km</b> away and conditions are <b>Safe</b>.\n\n";
            }

        } elseif (($fromWeather && $fromWeather->risk_status === 'Caution') || ($toWeather && $toWeather->risk_status === 'Caution')) {
            $adviceMsg .= "🟡 <b>Recommendation:</b> Travel with caution. Some rough weather detected.\n\n";
        } else {
            $adviceMsg .= "✅ <b>Trip Advice:</b> Weather & Route Path look good for sailing!\n\n";
        }


        // Find next departure from NOW
        $schedule = Schedule::where('origin_port_id', $fromPort->id)
            ->where('destination_port_id', $toPort->id)
            ->where('departure_time', '>=', now())
            ->orderBy('departure_time', 'asc')
            ->with('ferry')
            ->first();

        if (! $schedule) {
            $msg = $adviceMsg . "❌ No upcoming trips found today from <b>{$fromPort->name}</b> to <b>{$toPort->name}</b>.";
             if ($messageId) $this->telegram->editMessageText($chatId, $messageId, $msg);
             else $this->telegram->sendMessage($chatId, $msg);
            return;
        }

        $time = \Carbon\Carbon::parse($schedule->departure_time)->format('h:i A');
        $date = \Carbon\Carbon::parse($schedule->departure_time)->format('d M Y');

        // Generate Visual Route Profile (ASCII Graph)
        $profileGraph = "<b>🛰 Route Scan Profile:</b>\n\n";
        
        // Origin
        $emoji = $fromWeather && $fromWeather->risk_status === 'High Risk' ? '🔴' : ($fromWeather && $fromWeather->risk_status === 'Caution' ? '🟡' : '🟢');
        $profileGraph .= "📍 <b>{$fromPort->name}:</b> {$emoji} " . ($fromWeather->wave_height ?? '?'). "m\n";
        
        // Mid-Sea Points
        foreach($routeAnalysis['checkpoints'] as $i => $cp) {
             $cpEmoji = $cp['wave_height'] > 2.5 ? '🔴' : ($cp['wave_height'] > 1.5 ? '🟡' : '🟢');
             $num = $i + 1;
             $profileGraph .= "🌊 <b>Mid-Sea {$num}:</b> {$cpEmoji} {$cp['wave_height']}m\n";
        }
        
        // Destination
        $emojiDest = $toWeather && $toWeather->risk_status === 'High Risk' ? '🔴' : ($toWeather && $toWeather->risk_status === 'Caution' ? '🟡' : '🟢');
        $profileGraph .= "📍 <b>{$toPort->name}:</b> {$emojiDest} " . ($toWeather->wave_height ?? '?'). "m\n\n";


        $message = "<b>🚢 Next Ferry Departure</b>\n\n".
            $adviceMsg.
            $profileGraph.
            "📍 <b>Route:</b> {$fromPort->name} ➡️ {$toPort->name}\n".
            "🕐 <b>Time:</b> $time\n".
            "📅 <b>Date:</b> $date\n".
            "⛴ <b>Ferry:</b> {$schedule->ferry->name}\n".
            "💵 <b>Price:</b> RM {$schedule->price}\n".
             "📡 <i>Analyzed 5 geospatial points along the route.</i>";

        if ($messageId) $this->telegram->editMessageText($chatId, $messageId, $message);
        else $this->telegram->sendMessage($chatId, $message);
    }

    /**
     * NLP-Lite: Handle human-like sentences (e.g. "Is it safe to go to Langkawi?")
     */
    protected function handleNaturalText($chatId, $text)
    {
        $text = strtolower($text);
        
        // 1. Detect Intent
        $isWeather = str_contains($text, 'weather') || str_contains($text, 'safe') || str_contains($text, 'risk') || str_contains($text, 'how is');
        $isSchedule = str_contains($text, 'schedule') || str_contains($text, 'time') || str_contains($text, 'when') || str_contains($text, 'trip');
        
        // 2. Extract Entities (Ports) using Fuzzy logic
        $recognizedPort = null;
        $ports = Port::all();
        foreach($ports as $port) {
            if (str_contains($text, strtolower($port->name))) {
                $recognizedPort = $port;
                break;
            }
        }
        
        if ($recognizedPort) {
            if ($isSchedule) {
                 $this->telegram->sendMessage($chatId, "🤖 I see you are asking about trips involving <b>{$recognizedPort->name}</b>. Let me find the schedule...");
                 // Trigger schedule flow (Ask for destination if only one port found)
                 $this->askForScheduleDestination($chatId, $recognizedPort->id);
            } else {
                 // Default to weather/safety
                 $this->telegram->sendMessage($chatId, "🤖 Checking safety status for <b>{$recognizedPort->name}</b>...");
                 $this->sendWeatherReport($chatId, $recognizedPort);
            }
            return;
        }

        // If no port found but intent is clear
        if ($isWeather) {
            $this->telegram->sendMessage($chatId, "🤖 You seem to be asking about the weather. Which jetty?");
             $keyboard = $this->getPortsKeyboard('w_');
            $this->telegram->sendMessage($chatId, "Select a jetty:", ['inline_keyboard' => $keyboard]);
            return;
        }
        
        // Fallback
        $this->telegram->sendMessage($chatId, "🤖 I'm sorry, I didn't quite catch that. I am an AI, but I'm trained specifically for Marine Safety.\n\nTry asking:\n'Is Langkawi safe?'\n'When is the next ferry to Kuala Perlis?'");
    }
    
    private function getPortsKeyboard($prefix, $excludeId = null)
    {
        $ports = Port::all();
        $buttons = [];
        $row = [];
        foreach ($ports as $port) {
            if ($excludeId && $port->id == $excludeId) continue;
            
            // Inline Keyboard Button format: ['text' => 'Label', 'callback_data' => 'Payload']
            $row[] = ['text' => $port->name, 'callback_data' => $prefix . $port->id];
            
            // 2 buttons per row
            if (count($row) == 2) {
                $buttons[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) $buttons[] = $row;
        return $buttons;
    }

    protected function handleNotify($chatId, $args)
    {
        if (count($args) < 2) {
             $this->telegram->sendMessage($chatId, "⚠️ Usage: <code>/notify [Origin] [Destination]</code>\nExample: <code>/notify Langkawi Kuala</code>");
             return;
        }

        $fromQuery = $args[0];
        $toQuery = $args[1];

        $fromPort = Port::where('name', 'LIKE', "%{$fromQuery}%")->first();
        $toPort = Port::where('name', 'LIKE', "%{$toQuery}%")->first();
        
        if (!$fromPort || !$toPort) {
            $this->telegram->sendMessage($chatId, "❌ Ports not found.");
            return;
        }

        // Create Subscription
        RouteSubscription::create([
            'user_id' => null, // Optional if we link user later
            'chat_id' => $chatId,
            'origin_port_id' => $fromPort->id,
            'destination_port_id' => $toPort->id,
            'is_active' => true
        ]);

        $this->telegram->sendMessage($chatId, "🔔 <b>Request Received!</b>\n\nI will monitor the weather for <b>{$fromPort->name} ➡️ {$toPort->name}</b> every hour.\n\nI'll send you a message automatically when it becomes safe to travel.");
    }

    private function findAlternativeSafePort($riskyPort)
    {
        // Get all ports except current
        $ports = Port::where('id', '!=', $riskyPort->id)->get();
        
        foreach($ports as $port) {
            if (!$port->latitude || !$port->longitude) continue;
            
            // Calculate distance
            $dist = $this->calculateDistance($riskyPort->latitude, $riskyPort->longitude, $port->latitude, $port->longitude);
            
            // If within 50km
            if ($dist <= 50) {
                // Check if SAFE
                $weather = $port->weatherData()->latest()->first();
                if ($weather && $weather->risk_status !== 'High Risk') {
                    return $port;
                }
            }
        }
        return null;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        return round($miles * 1.609344, 1);
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
