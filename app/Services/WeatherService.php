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
            // USING OPENWEATHERMAP API
            $apiKey = env('VITE_OPENWEATHER_API_KEY');
            
            $response = Http::get("https://api.openweathermap.org/data/2.5/weather", [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $apiKey,
                'units' => 'metric'
            ]);

            $windSpeed = 0;

            $visibility = 10;
            $waveHeight = 0;

            if ($response->successful()) {
                $data = $response->json();
                
                // Wind Speed: OWM returns m/s, convert to km/h
                $windSpeed = ($data['wind']['speed'] ?? 0) * 3.6;
                
                // Visibility: OWM returns meters, convert to km
                $visibility = ($data['visibility'] ?? 10000) / 1000;
                

            }

            // Note: OpenWeatherMap Standard does not provide Wave Height.
            // We will fetch wave data from Open-Meteo Marine (Free) to maintain Risk Model functionality,
            // or estimate it based on wind speed if strictly necessary to avoid all other APIs.
            // For now, keeping Open-Meteo Marine for WAVES ONLY as OWM Standard lacks this.
             $marineResponse = Http::get('https://marine-api.open-meteo.com/v1/marine', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'wave_height',
                'timezone' => 'Asia/Singapore',
            ]);
            
            if ($marineResponse->successful()) {
                $waveHeight = $marineResponse->json('current')['wave_height'] ?? 0;
            }

            $weatherData = [
                'wind_speed' => $windSpeed,
                'wave_height' => $waveHeight,
                'visibility' => $visibility,

                'tide_level' => 1.0, // Mock for now
                'recorded_at' => now(),
            ];

            // AI Risk Analysis (Delegated to Main Engine)
            $riskData = app(\App\Services\GeoIntelligenceService::class)->calculateRisk($weatherData);

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

    // analyzeRisk moved to GeoIntelligenceService

    /**
     * Fetch weather data for a specific coordinate (Ad-hoc / Mid-Sea).
     * Does NOT save to database.
     * Caches result for 60 minutes to improve performance.
     */
    public function getMarineForecast($lat, $lon)
    {
        // Round coordinates to increase cache hit rate
        $lat = round($lat, 2);
        $lon = round($lon, 2);
        $cacheKey = "marine_forecast_{$lat}_{$lon}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 60 * 60, function () use ($lat, $lon) {
            try {
                // USING OPENWEATHERMAP API
                $apiKey = env('VITE_OPENWEATHER_API_KEY');
                
                $response = Http::timeout(5)->get("https://api.openweathermap.org/data/2.5/weather", [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $apiKey,
                    'units' => 'metric'
                ]);

                // Keep Marine API for waves
                $marineResponse = Http::timeout(3)->get('https://marine-api.open-meteo.com/v1/marine', [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'current' => 'wave_height,wave_direction,wave_period,swell_wave_height',
                    'timezone' => 'Asia/Singapore',
                ]);

                $windSpeed = 0;
                $waveHeight = 0;
                $waveDirection = 0;
                $wavePeriod = 0;
                $swellHeight = 0;
                $visibility = 10;


                if ($response->successful()) {
                    $data = $response->json();
                    $windSpeed = ($data['wind']['speed'] ?? 0) * 3.6; // m/s to km/h
                    $visibility = ($data['visibility'] ?? 10000) / 1000; // m to km

                }

                if ($marineResponse->successful()) {
                    $marineData = $marineResponse->json('current');
                    $waveHeight = $marineData['wave_height'] ?? 0;
                    $waveDirection = $marineData['wave_direction'] ?? 0;
                    $wavePeriod = $marineData['wave_period'] ?? 0;
                    $swellHeight = $marineData['swell_wave_height'] ?? 0;
                }

                $weatherData = [
                    'wind_speed' => $windSpeed,
                    'wave_height' => $waveHeight,
                    'wave_direction' => $waveDirection,
                    'wave_period' => $wavePeriod,
                    'swell_height' => $swellHeight,
                    'visibility' => $visibility,

                ];

                // AI Risk Analysis (Delegated to Main Engine)
                $riskData = app(\App\Services\GeoIntelligenceService::class)->calculateRisk($weatherData);

                return array_merge($weatherData, $riskData);

            } catch (\Exception $e) {
                Log::error("Marine forecast fetch failed: ".$e->getMessage());
                return null;
            }
        });
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
