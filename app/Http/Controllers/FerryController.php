<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FerryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ferries = Cache::remember('ferries.all', 3600, function () {
            return Ferry::latest()->get();
        });

        return Inertia::render('Ferries/Index', [
            'ferries' => $ferries,
        ]);
    }

    /**
     * Display a public listing of the resource.
     */
    public function publicIndex()
    {
        $ferries = Cache::remember('ferries.all', 3600, function () {
            return Ferry::latest()->get();
        });

        return Inertia::render('Ferries/PublicIndex', [
            'ferries' => $ferries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'length_ft' => 'nullable|string|max:50',
            'booking_url' => 'nullable|url|max:255',
            'ticket_type' => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('ferries', 'public');
            $validated['image_path'] = '/storage/'.$path;
        }

        // Remove 'image' from inputs as it's not in DB, we use image_path
        unset($validated['image']);

        // Set defaults for non-nullable fields if they are null
        $validated['price'] = $validated['price'] ?? 0;

        Ferry::create($validated);

        Cache::forget('ferries.all');

        return redirect()->back()->with('success', 'Ferry added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ferry $ferry)
    {
        return Inertia::render('Ferries/Show', [
            'ferry' => $ferry->load('schedules'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ferry $ferry)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'length_ft' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:10240',
            'amenities' => 'nullable|array',
            'booking_url' => 'nullable|url|max:255',
            'ticket_type' => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($ferry->image_path) {
                $oldPath = str_replace('/storage/', '', $ferry->image_path);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('image')->store('ferries', 'public');
            $validated['image_path'] = '/storage/'.$path;
        }

        unset($validated['image']);

        // Remove null values to avoid overwriting with null if only partial update intended?
        // Typically update request sends all fields or we merge.
        // Assuming Inertia form sends all fields.

        // Ensure non-nullable fields are not set to null
        if (array_key_exists('price', $validated) && is_null($validated['price'])) {
            $validated['price'] = 0;
        }

        $ferry->update($validated);

        Cache::forget('ferries.all');

        return redirect()->back()->with('success', 'Ferry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ferry $ferry)
    {
        $ferry->delete();

        Cache::forget('ferries.all');

        return redirect()->back()->with('success', 'Ferry deleted successfully.');
    }

    /**
     * Display the specified resource for public view.
     */
    public function publicShow(Ferry $ferry)
    {
        $ferry->load(['schedules']);

        return Inertia::render('Ferries/PublicShow', [
            'ferry' => $ferry,
        ]);
    }
}
