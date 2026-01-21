<?php

namespace App\Http\Controllers;

use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class WeatherController extends Controller
{
    // Display weather info for a specific port
    public function show(Request $request, Port $port)
    {
        $weather = null;

        // Auto-fetch logic if requested via query param
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
     * This function mimics what a scheduled job would do:
     * 1. Gather weather data
     * 2. Send to Python AI for prediction
     * 3. Save result
     */
    public function store(Request $request, Port $port)
    {
        $validated = $request->validate([
            'wind_speed' => 'required|numeric|min:0|max:500', // Max 500 km/h (World record ~408)
            'wave_height' => 'required|numeric|min:0|max:50', // Max 50m (Highest rogue wave ~30m)
            'visibility' => 'nullable|numeric|min:0|max:200',
            'tide_level' => 'nullable|numeric|min:-10|max:20',
            'precipitation' => 'nullable|numeric|min:0|max:1000',
        ], [
            'wind_speed.max' => 'Wind speed cannot exceed 500 km/h. That is stronger than a tornado!',
            'wave_height.max' => 'Wave height cannot exceed 50 meters. That is a tsunami!',
            'precipitation.max' => 'Precipitation amount is unrealistically high.',
        ]);

        // Call Python AI Service
        $riskScore = 0;
        $status = 'Safe'; // Default

        try {
            $response = Http::post('http://127.0.0.1:5000/predict-risk', $validated);
            if ($response->successful()) {
                $riskScore = $response->json('risk_score');
                $status = $response->json('risk_status') ?? 'Safe';
            }
        } catch (\Exception $e) {
            // Fallback: Basic logic if AI service is down
            if ($validated['wind_speed'] > 40 || $validated['wave_height'] > 2.0) {
                $riskScore = 80;
                $status = 'High Risk';
            } elseif ($validated['wind_speed'] > 25) {
                $riskScore = 50;
                $status = 'Caution';
            }
        }

        // ... (Prediction Logic) ...

        $weather = $port->weatherData()->create(array_merge($validated, [
            'recorded_at' => now(),
            'risk_score' => $riskScore,
            'risk_status' => $status,
        ]));

        // FIX: If this is an internal call (from refresh/auto-fetch), we should NOT redirect back.
        // We should instead allow the 'refresh' method to finish and let 'show' render the view.
        if ($request->header('X-Internal-Call')) {
            return $weather;
        }

        return back()->with('success', 'Weather data updated and risk calculated.');
    }

    /**
     * Fetch real weather data from Windy API (Forecast)
     */
    public function refresh(Port $port)
    {
        // Windy Point Forecast API (v2)
        // Note: This requires a paid key usually. If the provided key is for map tiles only,
        // we might fail here. We will fallback to Open-Meteo if Windy fails just to ensure user gets data.

        $apiKey = env('VITE_WINDY_API_KEY');
        $lat = $port->latitude;
        $lon = $port->longitude;

        try {
            // Prepare the payload for Windy API
            // Windy API expects a POST request with specific JSON structure
            $response = Http::post('https://api.windy.com/api/point-forecast/v2', [
                'lat' => $lat,
                'lon' => $lon,
                'model' => 'gfs',
                'parameters' => ['wind', 'waves', 'rain', 'visibility'],
                'levels' => ['surface'],
                'key' => $apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Windy returns arrays of values for timestamps. We just take the current one (index 0 or nearest).
                // Structure depends on API version. Assuming standard structure:

                // Fallback implementation using Open-Meteo (Free, Reliable for demo) if Windy format is complex/paid-only access
                // PROCEED WITH OPEN-METEO as it mimics the data request perfectly for this exercise
                // while we use Windy for the visual map.
                // Real implementation of Windy Point Forecast requires specific professional plan details.
            }

            // USING OPEN-METEO as a reliable backend data source for "Wind Speed, Wave Height, Rain"
            // to ensure the AI has data to work with immediately.
            $omResponse = Http::get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $lat,
                'longitude' => $lon,
                'current' => 'wind_speed_10m,precipitation,visibility',
                'hourly' => 'wave_height', // Marine API is separate usually, using standard or estimation
                'wind_speed_unit' => 'kmh',
            ]);

            // For wave height, we might need the Marine API from Open-Meteo
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
                $visibility = ($current['visibility'] ?? 10000) / 1000; // convert m to km
            }

            if ($marineResponse->successful()) {
                $waveHeight = $marineResponse->json('current')['wave_height'] ?? 0;
            }

            // Simulate a "Store" request
            $request = new Request([
                'wind_speed' => $windSpeed,
                'wave_height' => $waveHeight,
                'visibility' => $visibility,
                'precipitation' => $precipitation,
                'tide_level' => 1.0,
            ]);

            // Tag this request as Internal so 'store' doesn't redirect
            $request->headers->set('X-Internal-Call', 'true');

            // Call store, which now returns the created weather model
            $newWeather = $this->store($request, $port);

            // If this refresh was called purely internally (e.g. from show method), return the object
            // BUT if it was called via a Route (button click), we must return a redirect response.
            // We can check if the original request to 'refresh' wants JSON or HTML.
            if (request()->wantsJson() || request()->header('X-Inertia')) {
                return back()->with('success', 'Live weather data fetched successfully.');
            }

            return $newWeather;

        } catch (\Exception $e) {
            if (request()->wantsJson() || request()->header('X-Inertia')) {
                return back()->with('error', 'Failed to fetch external data.');
            }

            return null;
        }
    }
}
