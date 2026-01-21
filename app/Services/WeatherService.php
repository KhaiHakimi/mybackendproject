<?php

namespace App\Services;

use App\Models\Port;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    /**
     * Fetch weather data for a port and store it.
     */
    public function updateWeatherForPort(Port $port)
    {
        $lat = $port->latitude;
        $lon = $port->longitude;

        try {
            // USING OPEN-METEO as a reliable backend data source
            $omResponse = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'wind_speed_10m,precipitation,visibility',
                'hourly' => 'wave_height',
                'wind_speed_unit' => 'kmh',
            ]);

            $marineResponse = Http::get('https://marine-api.open-meteo.com/v1/marine', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'wave_height',
                'timezone' => 'Asia/Singapore',
            ]);

            $windSpeed = 0;
            $precipitation = 0;
            $visibility = 10;
            $waveHeight = 0;

            if ($omResponse->successful()) {
                $current = $omResponse->json('current');
                $windSpeed = $current['wind_speed_10m'] ?? 0;
                $precipitation = $current['precipitation'] ?? 0;
                $visibility = ($current['visibility'] ?? 10000) / 1000;
            }

            if ($marineResponse->successful()) {
                $waveHeight = $marineResponse->json('current')['wave_height'] ?? 0;
            }

            $weatherData = [
                'wind_speed' => $windSpeed,
                'wave_height' => $waveHeight,
                'visibility' => $visibility,
                'precipitation' => $precipitation,
                'tide_level' => 1.0, // Mock for now
                'recorded_at' => now(),
            ];

            // AI Risk Analysis
            $riskData = $this->analyzeRisk($weatherData);

            $finalData = array_merge($weatherData, $riskData);

            // Save to DB
            $weather = $port->weatherData()->create($finalData);

            // Check for Alerts
            if ($finalData['risk_status'] === 'High Risk') {
                $this->triggerAlert($port, $finalData);
            }

            return $weather;

        } catch (\Exception $e) {
            Log::error("Weather fetch failed for port {$port->id}: ".$e->getMessage());

            return null;
        }
    }

    private function analyzeRisk($data)
    {
        // Try Python Service
        try {
            $response = Http::timeout(2)->post('http://127.0.0.1:5000/predict-risk', $data);
            if ($response->successful()) {
                return [
                    'risk_score' => $response->json('risk_score'),
                    'risk_status' => $response->json('risk_status') ?? 'Safe',
                ];
            }
        } catch (\Exception $e) {
            // Fallback
        }

        // Fallback Logic
        $riskScore = 0;
        $status = 'Safe';

        if ($data['wind_speed'] > 40 || $data['wave_height'] > 2.0) {
            $riskScore = 80;
            $status = 'High Risk';
        } elseif ($data['wind_speed'] > 25) {
            $riskScore = 50;
            $status = 'Caution';
        }

        return ['risk_score' => $riskScore, 'risk_status' => $status];
    }

    private function triggerAlert(Port $port, $data)
    {
        $message = "🚨 <b>HIGH RISK ALERT</b> 🚨\n\n".
            "📍 <b>Port:</b> {$port->name}\n".
            "⚠️ <b>Risk Score:</b> {$data['risk_score']}%\n".
            "🌊 <b>Status:</b> {$data['risk_status']}\n\n".
            "Conditions:\n".
            "💨 Wind: {$data['wind_speed']} km/h\n".
            "🌊 Waves: {$data['wave_height']} m\n".
            'Please avoid sea travel until conditions improve.';

        // 1. Notify Admin
        $adminChatId = config('services.telegram.chat_id');
        if ($adminChatId) {
            \App\Jobs\SendTelegramAlert::dispatch($adminChatId, $message);
        }

        // 2. Notify Users
        \App\Models\User::whereNotNull('telegram_chat_id')->chunk(50, function ($users) use ($message) {
            foreach ($users as $user) {
                \App\Jobs\SendTelegramAlert::dispatch($user->telegram_chat_id, $message);
            }
        });
    }
}
