<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use SplFileObject;

class DashboardController extends Controller
{
    public function index()
    {
        $adminStats = null;
        $user = auth()->user();

        if ($user && $user->is_admin) {
            $adminStats = [
                'total_users' => \App\Models\User::count(),
                'total_ferries' => \App\Models\Ferry::count(),
                'active_voyages' => \App\Models\Schedule::where('departure_time', '>=', now())->count(),
                'total_schedules' => \App\Models\Schedule::count(), // Added total_schedules as it uses it in Vue
                'total_ports' => \App\Models\Port::count(),
                'total_reviews' => \App\Models\Review::count(),
            ];
            // Note: System logs are no longer loaded here to improve performance.
            // They should be fetched asynchronously if needed.
        }

        return Inertia::render('Dashboard', [
            'ports' => \App\Models\Port::whereHas('departures')->orWhereHas('arrivals')->with('latestWeather')->get(),
            'adminStats' => $adminStats,
            'systemLogs' => [], // Empty by default
            'telegramCode' => $user ? $user->telegram_verification_code : null,
            'telegramBotName' => config('services.telegram.bot_username'),
            'isTelegramLinked' => $user ? ! empty($user->telegram_chat_id) : false,
        ]);
    }

    public function systemLogs()
    {
        if (! auth()->user() || ! auth()->user()->is_admin) {
            return response()->json([]);
        }

        $systemLogs = [];
        $logPath = storage_path('logs/laravel.log');

        if (file_exists($logPath)) {
            try {
                $file = new SplFileObject($logPath, 'r');
                $file->seek(PHP_INT_MAX);
                $lastLine = $file->key();

                // Get last 50 lines efficiently
                $lines = [];
                // Ensure we don't seek before 0
                $start = max(0, $lastLine - 50);

                // If file is empty or near empty
                if ($lastLine > 0) {
                    $file->seek($start);

                    while (! $file->eof()) {
                        $line = $file->current();
                        if (trim($line)) {
                            $lines[] = $line;
                        }
                        $file->next();
                    }
                }

                $lines = array_reverse($lines);

                foreach ($lines as $line) {
                    // Try to match standard Laravel log format: [Date Time] env.LEVEL: Message
                    if (preg_match('/^\[(?<date>.*?)\] (?<env>\w+)\.(?<level>\w+): (?<message>.*)/', $line, $matches)) {
                        $systemLogs[] = [
                            'date' => $matches['date'],
                            'level' => $matches['level'],
                            'message' => $matches['message'],
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Log file might be empty or not readable, return empty
            }
        }

        return response()->json($systemLogs);
    }

    public function geoAnalysis(\Illuminate\Http\Request $request, \App\Services\GeoIntelligenceService $geoService)
    {
        $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $analysis = $geoService->analyzeLocation($request->lat, $request->lng);

        return response()->json($analysis);
    }

    public function analyzeRoute(\Illuminate\Http\Request $request, \App\Services\GeoIntelligenceService $geoService)
    {
        $request->validate([
            'origin_id' => 'required|exists:ports,id',
            'destination_id' => 'required|exists:ports,id',
        ]);

        $origin = \App\Models\Port::find($request->origin_id);
        $dest = \App\Models\Port::find($request->destination_id);

        $analysis = $geoService->analyzeRouteViability($origin, $dest);

        return response()->json($analysis);
    }
}
