<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use App\Models\Port;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $nearestPorts = collect();
        $originPortIds = [];

        // 1. Handle Geolocation (Find 3 Nearest Ports)
        if ($latitude && $longitude) {
            $nearestPorts = Port::select('*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$latitude, $longitude, $latitude]
                )
                ->orderBy('distance')
                ->limit(3)
                ->get();

            if ($nearestPorts->isNotEmpty()) {
                $originPortIds = $nearestPorts->pluck('id')->toArray();
            }
        }

        // 2. Base Query
        $query = Schedule::with(['ferry', 'origin', 'destination']);

        // 3. Apply Filters

        // Date Filter (Default to today)
        $date = $request->query('date', now()->toDateString());
        $query->whereDate('departure_time', $date);

        // Location Filter (from Geolocation)
        if (! empty($originPortIds)) {
            $query->whereIn('origin_port_id', $originPortIds);
        }

        // Destination Filter
        if ($request->filled('destination_port_id')) {
            $query->where('destination_port_id', $request->query('destination_port_id'));
        }

        // Time Filter
        if ($request->filled('time_of_day')) {
            $timeOfDay = $request->query('time_of_day'); // morning, afternoon, evening
            if ($timeOfDay === 'morning') {
                $query->whereTime('departure_time', '>=', '00:00:00')
                    ->whereTime('departure_time', '<', '12:00:00');
            } elseif ($timeOfDay === 'afternoon') {
                $query->whereTime('departure_time', '>=', '12:00:00')
                    ->whereTime('departure_time', '<', '18:00:00');
            } elseif ($timeOfDay === 'evening') {
                $query->whereTime('departure_time', '>=', '18:00:00')
                    ->whereTime('departure_time', '<=', '23:59:59');
            }
        }

        // State Filter (Handled crudely by matching port addresses or IDs if we had that map here).
        // Since the user wants a Button for state, we can try to filter ports by state if query param exists.
        // For now, we'll keep State filtering client-side or rely on the frontend passing explicit port IDs if it wants to be strict?
        // Actually, the user asked for buttons. Let's let the Frontend handle State -> Port mapping and pass port IDs?
        // No, typically 'Near Me' overrides other location filters.
        // If 'state' is passed, we could filter origin_port to be in that state, but we lack DB column.
        // Let's stick to the Frontend filtering for State for now, as it worked before.

        $schedules = $query->orderBy('departure_time')->get();

        return Inertia::render('Schedules/Index', [
            'schedules' => $schedules,
            'ferries' => Ferry::all(),
            'ports' => Port::all(),
            'nearestPorts' => $nearestPorts, // Returns Collection
            'userLocation' => $latitude && $longitude ? ['lat' => $latitude, 'lng' => $longitude] : null,
            'filters' => $request->only(['date', 'destination_port_id', 'time_of_day', 'latitude', 'longitude', 'state']),
            'initialDate' => $date,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ferry_id' => 'required|exists:ferries,id',
            'origin_port_id' => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            'departure_time' => 'required|date',
            'arrival_time' => 'required|date|after:departure_time',
            'price' => 'required|numeric|min:0',
        ]);

        Schedule::create($validated);

        return redirect()->back()->with('success', 'Schedule created successfully.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->back()->with('success', 'Schedule deleted.');
    }
}
