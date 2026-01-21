<?php

namespace App\Http\Controllers;

use App\Jobs\FetchWeatherForPort;
use App\Models\Port;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WeatherController extends Controller
{
    // Display weather info for a specific port
    public function show(Request $request, Port $port)
    {
        $weather = null;

        // Auto-fetch logic if requested via query param (Accessible to all)
        if ($request->has('auto_fetch')) {
            // This now returns the NEW weather object directly
            $weather = $this->refresh($port);
        }

        // If auto-fetch didn't happen or failed, get latest from DB
        if (! $weather) {
            $weather = $port->weatherData()->latest()->first();
        }

        return Inertia::render('Weather/Show', [
            'port' => $port,
            'weather' => $weather,
            'risk_analysis' => $this->analyzeRisk($weather),
        ]);
    }

    // Helper to interpret the risk score
    private function analyzeRisk($weather)
    {
        if (! $weather) {
            return ['status' => 'Unknown', 'color' => 'gray'];
        }

        // Use the explicit status from the AI model if available
        if (! empty($weather->risk_status)) {
            $status = $weather->risk_status;
            $color = match ($status) {
                'High Risk' => 'red',
                'Caution' => 'yellow',
                'Safe' => 'green',
                default => 'gray'
            };

            return ['status' => $status, 'color' => $color];
        }

        // Legacy/Fallback score-based logic
        $score = $weather->risk_score;

        if ($score < 30) {
            return ['status' => 'Safe', 'color' => 'green'];
        }
        if ($score < 70) {
            return ['status' => 'Caution', 'color' => 'yellow'];
        }

        return ['status' => 'High Risk', 'color' => 'red'];
    }

    /**
     * Store new weather data (Manual or via API callback)
     */
    /**
     * Store new weather data (Manual Entry)
     */
    public function store(Request $request, Port $port)
    {
        $validated = $request->validate([
            'wind_speed' => 'required|numeric|min:0',
            'wave_height' => 'required|numeric|min:0',
            'visibility' => 'nullable|numeric|min:0',
            'tide_level' => 'nullable|numeric',
            'precipitation' => 'nullable|numeric|min:0',
        ]);

        // Basic Risk Logic (Duplicated from Service for manual overrides)
        $riskScore = 0;
        $status = 'Safe';

        if ($validated['wind_speed'] > 40 || $validated['wave_height'] > 2.0) {
            $riskScore = 80;
            $status = 'High Risk';
        } elseif ($validated['wind_speed'] > 25) {
            $riskScore = 50;
            $status = 'Caution';
        }

        $weather = $port->weatherData()->create(array_merge($validated, [
            'recorded_at' => now(),
            'risk_score' => $riskScore,
            'risk_status' => $status,
        ]));

        // Trigger Async Alert if High Risk
        if ($status === 'High Risk') {
            $message = "🚨 <b>HIGH RISK ALERT (Simulated)</b> 🚨\n\n".
                   "📍 <b>Port:</b> {$port->name}\n".
                   "⚠️ <b>Risk Score:</b> {$riskScore}%\n".
                   "🌊 <b>Status:</b> {$status}\n".
                    "Conditions:\n".
                   "💨 Wind: {$validated['wind_speed']} km/h\n".
                   "🌊 Waves: {$validated['wave_height']} m\n";

            $adminChatId = config('services.telegram.chat_id');
            if ($adminChatId) {
                \App\Jobs\SendTelegramAlert::dispatch($adminChatId, $message);
            }
        }

        if ($request->header('X-Internal-Call')) {
            return $weather;
        }

        return back()->with('success', 'Weather simulated/stored successfully.');
    }

    /**
     * Fetch real weather data from API (Now uses Service)
     */
    public function refresh(Port $port, WeatherService $service)
    {
        $weather = $service->updateWeatherForPort($port);

        if (request()->wantsJson() || request()->header('X-Inertia')) {
            return back()->with(
                $weather ? 'success' : 'error',
                $weather ? 'Live analysis complete.' : 'Service unavailable. Try again.'
            );
        }

        return $weather;
    }

    /**
     * Refresh weather for ALL ports (Async via Queues)
     */
    public function refreshAll()
    {
        $ports = Port::all();
        $count = 0;

        foreach ($ports as $port) {
            FetchWeatherForPort::dispatch($port);
            $count++;
        }

        return response()->json([
            'success' => true,
            'message' => "Queued weather updates for {$count} ports.",
            'total' => $count,
        ]);
    }
}
