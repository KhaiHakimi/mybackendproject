<?php

namespace App\Http\Controllers;

use App\Jobs\FetchWeatherForPort;
use App\Models\Port;
use App\Services\WeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
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

    public function windData(Request $request)
    {
        // Cache for 1 hour
        // v67: FORCE POSITIVE DY (Fix Unavailable?)
        return Cache::remember('marine_wind_data_grid_v67', 3600, function () {
            return $this->transformWindDataSimulation([]);
        });
    }



    private function transformWindDataSimulation($data)
    {
        // Grid: 0 to 360, 90 to -90
        $nx = 360; 
        $ny = 181;
        $gridSize = $nx * $ny;
        
        // Strong Wind
        $uData = array_fill(0, $gridSize, 10.0); 
        $vData = array_fill(0, $gridSize, 5.0); 

        return [
             [
                'header' => [
                    'parameterCategory' => 2, 'parameterNumber' => 2,
                    'nx' => $nx, 'ny' => $ny,
                    'lo1' => 0.0, 'la1' => 90.0,
                    'dx' => 1.0, 'dy' => 1.0, // POSITIVE step size
                    'refTime' => now()->toIso8601String(),
                ],
                'data' => $uData
             ],
             [
                'header' => [
                    'parameterCategory' => 2, 'parameterNumber' => 3,
                    'nx' => $nx, 'ny' => $ny,
                    'lo1' => 0.0, 'la1' => 90.0,
                    'dx' => 1.0, 'dy' => 1.0, // POSITIVE step size
                    'refTime' => now()->toIso8601String(),
                ],
                'data' => $vData
             ]
        ];
    }

    public function waveData(Request $request)
    {
        // Cache data for 1 hour
        // v60: Reverted to OpenMeteo/Sim (Stormglass removed)
        return Cache::remember('marine_wave_data_grid_v60', 3600, function () {
             return $this->fetchWaveDataWithFallback();
        });
    }







    private function fetchWaveDataWithFallback()
    {
        try {
            $realData = $this->fetchOpenMeteoWaveData();
            if ($this->hasValidGridData($realData)) {
                return $realData;
            }
        } catch (\Exception $e) {
            \Log::error("Wave Data Fetch Failed: " . $e->getMessage());
        }

        \Log::warning("OpenMeteo Wave Data Empty/Failed - Using Simulation Fallback");
        return $this->generateSimulationWaves();
    }

    private function hasValidGridData($data)
    {
        if (empty($data)) return false;
        $sample = $data[0]['data'] ?? [];
        foreach ($sample as $val) {
            if ($val != 0) return true;
        }
        return false;
    }

    private function generateSimulationWaves()
    {
        // GLOBAL STANDARD GRID (-180 to 180)
        // Most robust for leaflet-velocity
        $nx = 360;
        $ny = 181;
        
        $la1 = 90.0;
        $la2 = -90.0;
        
        $lo1 = -180.0; // Standard start
        $lo2 = 179.0;
        
        $dx = 1.0;
        $dy = -1.0;
        
        $gridSize = $nx * $ny;
        $uData = array_fill(0, $gridSize, 0.0);
        $vData = array_fill(0, $gridSize, 0.0);
        
        for ($latIdx = 0; $latIdx < $ny; $latIdx++) {
            $lat = $la1 + ($latIdx * $dy);
            
            for ($lonIdx = 0; $lonIdx < $nx; $lonIdx++) {
                $lon = $lo1 + ($lonIdx * $dx);
                $i = ($latIdx * $nx) + $lonIdx;

                // VISIBLE WAVES EVERYWHERE 
                $height = 4.0 + (1.0 * sin($lat * 0.2)); 
                $dir = 225; // SW Flow
                
                $rad = deg2rad($dir);
                $uData[$i] = round(-$height * sin($rad), 3);
                $vData[$i] = round(-$height * cos($rad), 3);
            }
        }
        
        $baseHeader = [
            'parameterCategory' => 2,
            'parameterNumber'   => 2,
            'shorthand'         => 'ugrd',
            'surface1Type'      => 103,
            'surface1Value'     => 10.0,
            'forecastTime'      => 0,
            'scanMode'          => 0,
            'nx'  => $nx,
            'ny'  => $ny,
            'lo1' => $lo1,
            'la1' => $la1,
            'lo2' => $lo2,
            'la2' => $la2,
            'dx'  => $dx,
            'dy'  => $dy,
            'refTime' => now()->setTimezone('UTC')->toIso8601String()
        ];

        return [
            [
                'header' => $baseHeader,
                'data'   => $uData,
            ],
            [
                'header' => array_merge($baseHeader, [
                    'parameterNumber' => 3, 
                    'shorthand' => 'vgrd'
                ]),
                'data' => $vData,
            ],
        ];
    }



    /**
     * Crude Land Mask for ASEAN Region to prevent waves on land.
     */


    private function fetchOpenMeteoWaveData()
    {
        // 1. Initialize Global Grid
        $nx = 360; $ny = 181; // 1-degree global grid matches the fetch step for simplicity
        $gridSize = $nx * $ny;
        $uData = array_fill(0, $gridSize, 0.0);
        $vData = array_fill(0, $gridSize, 0.0);

        // 2. Target Region (Reduced to 1.0 degree step to safely stay within API limits)
        $latMin = -2; $latMax = 14; 
        $lonMin = 94; $lonMax = 126;
        
        $lats = []; $lons = [];
        // Step 1.0 degree
        for ($lat = $latMax; $lat >= $latMin; $lat -= 1.0) { 
             for ($lon = $lonMin; $lon <= $lonMax; $lon += 1.0) {
                 $lats[] = $lat;
                 $lons[] = $lon;
             }
        }

        // 3. Fetch
        $chunkSize = 60; // Safer chunk size
        $totalPoints = count($lats);
        $results = [];

        try {
            for ($i = 0; $i < $totalPoints; $i += $chunkSize) {
                $chunkLats = array_slice($lats, $i, $chunkSize);
                $chunkLons = array_slice($lons, $i, $chunkSize);

                $response = Http::timeout(5)->get("https://marine-api.open-meteo.com/v1/marine", [
                    'latitude' => implode(',', $chunkLats),
                    'longitude' => implode(',', $chunkLons),
                    'hourly' => 'wave_height,wave_direction',
                    'forecast_days' => 1 
                ]);

                if ($response->failed() || $response->status() == 429) {
                    \Log::warning("OpenMeteo 429/Fail", ['status' => $response->status()]); 
                    return []; 
                }
                
                $chunkResult = $response->json();
                if (!is_array($chunkResult)) continue;
                if (!isset($chunkResult[0])) $chunkResult = [$chunkResult];
                
                $results = array_merge($results, $chunkResult);
                usleep(250000); // 250ms delay aggressively prevents rate limits
            }

            // 4. Map
            $currentHourIndex = (int) gmdate('G');
            $mappedCount = 0;

            foreach ($results as $idx => $pointData) {
                if (!isset($lats[$idx])) break;
                if (!isset($pointData['hourly']['wave_height'][$currentHourIndex])) continue;

                $height = $pointData['hourly']['wave_height'][$currentHourIndex];
                $dir = $pointData['hourly']['wave_direction'][$currentHourIndex];
                
                if (!$height || $height <= 0) continue;
                $mappedCount++;

                $ptLat = $lats[$idx];
                $ptLon = $lons[$idx];
                
                // Map to 1-degree Global Grid
                // 90 -> 0, -90 -> 180
                $latIdx = (int)round(90 - $ptLat);
                $lonIdx = (int)round($ptLon + 180);
                
                if ($lonIdx >= $nx) $lonIdx = 0;
                $arrayIndex = ($latIdx * $nx) + $lonIdx;
                
                if ($arrayIndex < 0 || $arrayIndex >= $gridSize) continue;

                $speedMs = $height; 
                $rad = deg2rad($dir);
                
                $uData[$arrayIndex] = round(-$speedMs * sin($rad), 3);
                $vData[$arrayIndex] = round(-$speedMs * cos($rad), 3);
            }
            
            if ($mappedCount < 5) return [];

            return [
                [
                    'header' => [
                        'parameterCategory' => 2, 'parameterNumber' => 2,
                        'nx' => $nx, 'ny' => $ny,
                        'lo1' => -180, 'la1' => 90, 
                        'dx' => 1.0, 'dy' => -1.0,
                        'refTime' => now()->toIso8601String(),
                    ],
                    'data' => $uData,
                ],
                [
                    'header' => [
                        'parameterCategory' => 2, 'parameterNumber' => 3,
                        'nx' => $nx, 'ny' => $ny,
                        'lo1' => -180, 'la1' => 90,
                        'dx' => 1.0, 'dy' => -1.0,
                        'refTime' => now()->toIso8601String(),
                    ],
                    'data' => $vData,
                ],
            ];

        } catch (\Exception $e) {
             \Log::error("OM Fetch Error: " . $e->getMessage());
             return [];
        }
    }


























}
