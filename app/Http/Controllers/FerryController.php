<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FerryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ferries = Ferry::latest()->get();
        return Inertia::render('Ferries/Index', [
            'ferries' => $ferries
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
        ]);

        Ferry::create($validated);

        return redirect()->back()->with('success', 'Ferry added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Ferry $ferry)
    {
        return Inertia::render('Ferries/Show', [
            'ferry' => $ferry->load('schedules')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ferry $ferry)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'capacity' => 'integer|min:1',
            'operator' => 'nullable|string|max:255',
        ]);

        $ferry->update($validated);

        return redirect()->back()->with('success', 'Ferry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ferry $ferry)
    {
        $ferry->delete();
        return redirect()->back()->with('success', 'Ferry deleted successfully.');
    }
}
