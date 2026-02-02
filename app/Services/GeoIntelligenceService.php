<?php

namespace App\Services;

use App\Models\Port;
use App\Services\WeatherService;

class GeoIntelligenceService
{
    protected $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * MAIN ENGINE: Centralized Risk Calculation Logic (The "Brain")
     * Accepts raw weather data and returns Risk Score % and Status.
     */
    public function calculateRisk($weatherData)
    {
        // 1. Python AI Integration (The Advanced Part)
        try {
            // Check if Flask is running (timeout 1s to be fast)
            $response = \Illuminate\Support\Facades\Http::timeout(1)->post('http://127.0.0.1:5000/predict-risk', $weatherData);
            if ($response->successful()) {
                return [
                    'risk_score' => $response->json('risk_score'),
                    'risk_status' => $response->json('risk_status') ?? 'Safe',
                    'source' => 'AI_ENGINE'
                ];
            }
        } catch (\Exception $e) {
            // Silently fail to fallback
        }

        // 2. Fallback Logic (Heuristic Engine)
        // If Python is offline, use this robust mathematical model
        $riskScore = 0;
        $status = 'Safe';
        
        $wind = $weatherData['wind_speed'] ?? 0;
        $waves = $weatherData['wave_height'] ?? 0;
        
        // Base Score on Waves (0-3m)
        // 0.5m = 10%, 1.0m = 30%, 2.0m = 70%, 3.0m = 100%
        $waveScore = min(($waves / 3.0) * 100, 100);
        
        // Base Score on Wind (0-60kmh)
        $windScore = min(($wind / 60.0) * 100, 100);
        
        // Weighted Average (Waves matter more for Ferries)
        $riskScore = round(($waveScore * 0.7) + ($windScore * 0.3));

        // Thresholds
        if ($riskScore >= 70) $status = 'High Risk';
        elseif ($riskScore >= 30) $status = 'Caution';
        
        // Override for Extreme/Dangerous conditions
        if ($waves > 2.0 || $wind > 50) {
            $status = 'High Risk';
            $riskScore = max($riskScore, 85);
        }

        return [
            'risk_score' => $riskScore,
            'risk_status' => $status,
            'source' => 'HEURISTIC_ENGINE'
        ];
    }
    /**
     * Find the nearest safe and unsafe ports based on user location.
     *
     * @param float $lat
     * @param float $lng
     * @return array
     */
    public function analyzeLocation($lat, $lng)
    {
        $ports = Port::all();
        $analyzedPorts = [];

        foreach ($ports as $port) {
            if (!$port->latitude || !$port->longitude) {
                continue;
            }

            $distance = $this->calculateDistance($lat, $lng, $port->latitude, $port->longitude);
            $weather = $port->weatherData()->latest()->first();
            
            // Default to 'Unknown' if no weather data
            $risk = $weather ? $weather->risk_status : 'Unknown';
            $riskScore = $weather ? $weather->risk_score : 0;

            $analyzedPorts[] = [
                'port' => $port,
                'distance' => $distance,
                'risk_status' => $risk,
                'risk_score' => $riskScore,
                'is_safe' => in_array($risk, ['Low Risk', 'Caution', 'Unknown']), // Treat Caution as relatively safe compared to High Risk
            ];
        }

        // Sort by distance
        usort($analyzedPorts, function ($a, $b) {
            return $a['distance'] <=> $b['distance'];
        });

        // Find nearest SAFE port
        $nearestSafe = null;
        foreach ($analyzedPorts as $p) {
            if ($p['is_safe']) {
                $nearestSafe = $p;
                break;
            }
        }

        // Find nearest regardless of safety (first in sorted list)
        $nearestAny = $analyzedPorts[0] ?? null;

        // Recommendation Logic
        $recommendation = null;
        if ($nearestAny && $nearestSafe) {
            if ($nearestAny['port']->id === $nearestSafe['port']->id) {
                $recommendation = [
                    'type' => 'success',
                    'message' => "Great news! The nearest jetty, <strong>{$nearestSafe['port']->name}</strong>, is currently safe for travel.",
                    'port_id' => $nearestSafe['port']->id
                ];
            } else {
                $diff = round($nearestSafe['distance'] - $nearestAny['distance'], 1);
                $recommendation = [
                    'type' => 'warning',
                    'message' => "Notice: The nearest jetty <strong>{$nearestAny['port']->name}</strong> is currently High Risk. We recommend <strong>{$nearestSafe['port']->name}</strong> instead (extra {$diff} km).",
                    'port_id' => $nearestSafe['port']->id
                ];
            }
        } elseif (!$nearestSafe && $nearestAny) {
             $recommendation = [
                'type' => 'danger',
                'message' => "Warning: All nearby jetties differ high risk conditions right now. Please exercise extreme caution.",
                'port_id' => $nearestAny['port']->id
            ];
        }

        return [
            'nearest_safe_port' => $nearestSafe,
            'nearest_any_port' => $nearestAny,
            'recommendation' => $recommendation
        ];
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        
        return ($miles * 1.609344); // Convert to KM
    }

    /**
     * ADVANCED GEOSPATIAL: Calculate Route Safety by scanning mid-sea Checkpoints
     */
    public function analyzeRouteViability(Port $origin, Port $dest)
    {
        // 1. Interpolate Route (Get 3 checkpoints in the middle of the ocean)
        $checkpoints = $this->getWaypoints(
            $origin->latitude, $origin->longitude, 
            $dest->latitude, $dest->longitude, 
            2
        );

        $deepSeaRisk = false;
        $maxWaveHeight = 0;
        $maxRiskScore = 0; // NEW: Track the highest percentage of risk along the path
        $checkpointData = [];

        // 2. Scan each checkpoint using Weather Service + AI
        foreach ($checkpoints as $index => $point) {
            // We use getMarineForecast (Stateless) instead of updateWeatherForPort (Stateful)
            $forecast = $this->weatherService->getMarineForecast($point['lat'], $point['lng']);
            
            if ($forecast) {
                // Check wave threshold
                if ($forecast['wave_height'] > $maxWaveHeight) {
                    $maxWaveHeight = $forecast['wave_height'];
                }
                
                // Track max risk score (from AI)
                if (isset($forecast['risk_score']) && $forecast['risk_score'] > $maxRiskScore) {
                     $maxRiskScore = $forecast['risk_score'];
                }
                
                // If deep sea waves are high (> 2.5m is rough)
                if ($forecast['wave_height'] > 2.5 || $forecast['risk_status'] === 'High Risk') {
                    $deepSeaRisk = true;
                }

                $checkpointData[] = [
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'wave_height' => $forecast['wave_height'],
                    'risk_score' => $forecast['risk_score'] ?? 0,
                    'status' => $forecast['risk_status']
                ];
            }
        }

        return [
            'is_deep_sea_risky' => $deepSeaRisk,
            'max_wave_height' => $maxWaveHeight,
            'route_risk_score' => $maxRiskScore,
            'checkpoints' => $checkpointData
        ];
    }

    /**
     * Linear Interpolation (Lerp) to find points between two coordinates
     */
    private function getWaypoints($lat1, $lon1, $lat2, $lon2, $steps)
    {
        $waypoints = [];
        for ($i = 1; $i <= $steps; $i++) {
            $fraction = $i / ($steps + 1);
            $waypoints[] = [
                'lat' => $lat1 + ($lat2 - $lat1) * $fraction,
                'lng' => $lon1 + ($lon2 - $lon1) * $fraction
            ];
        }
        return $waypoints;
    }
}
