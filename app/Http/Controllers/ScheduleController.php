<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Ferry;
use App\Models\Port;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $nearestPort = null;
        $originPortId = null;

        if ($latitude && $longitude) {
            // Find nearest port using Haversine formula
            $nearestPort = Port::select('*')
                ->selectRaw(
                    '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                    [$latitude, $longitude, $latitude]
                )
                ->orderBy('distance')
                ->first();

            if ($nearestPort) {
                $originPortId = $nearestPort->id;
            }
        }

        $query = Schedule::with(['ferry', 'origin', 'destination']);

        if ($originPortId) {
            $query->where('origin_port_id', $originPortId);
        }

        $schedules = $query->latest()->get();
            
        return Inertia::render('Schedules/Index', [
            'schedules' => $schedules,
            'ferries' => Ferry::all(),
            'ports' => Port::all(),
            'nearestPort' => $nearestPort,
            'userLocation' => $latitude && $longitude ? ['lat' => $latitude, 'lng' => $longitude] : null,
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
            'price' => 'required|numeric|min:0'
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
