<?php

namespace App\Http\Controllers;

use App\Models\Ferry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'ferries' => $ferries,
        ]);
    }

    /**
     * Display a public listing of the resource.
     */
    public function publicIndex()
    {
        $ferries = Ferry::latest()->get();

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
            'price' => 'nullable|string|max:255',
            'length_ft' => 'nullable|string|max:50',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
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
        $validated['rating'] = $validated['rating'] ?? 0;
        $validated['reviews_count'] = $validated['reviews_count'] ?? 0;
        // Price is now string/nullable, but if logic needs it not null:
        $validated['price'] = $validated['price'] ?? 'N/A';

        Ferry::create($validated);

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
            'price' => 'nullable|string|max:255',
            'length_ft' => 'nullable|string|max:50',
            'image' => 'nullable|image|max:10240',
            'amenities' => 'nullable|array',
            'rating' => 'nullable|numeric|min:0|max:5',
            'reviews_count' => 'nullable|integer|min:0',
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
        if (array_key_exists('rating', $validated) && is_null($validated['rating'])) {
            $validated['rating'] = 0;
        }
        if (array_key_exists('reviews_count', $validated) && is_null($validated['reviews_count'])) {
            $validated['reviews_count'] = 0;
        }
        // Price logic updated for string
        if (array_key_exists('price', $validated) && is_null($validated['price'])) {
            $validated['price'] = 'N/A';
        }

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

    /**
     * Display the specified resource for public view.
     */
    public function publicShow(Ferry $ferry)
    {
        $ferry->load(['reviews.user', 'schedules']);

        // Calculate rating distribution
        $ratingDistribution = [
            5 => $ferry->reviews->where('rating', 5)->count(),
            4 => $ferry->reviews->where('rating', 4)->count(),
            3 => $ferry->reviews->where('rating', 3)->count(),
            2 => $ferry->reviews->where('rating', 2)->count(),
            1 => $ferry->reviews->where('rating', 1)->count(),
        ];

        return Inertia::render('Ferries/PublicShow', [
            'ferry' => $ferry,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }

    /**
     * Store a new review for a ferry.
     */
    public function storeReview(Request $request, Ferry $ferry)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $ferry->reviews()->create([
            'user_id' => auth()->id(),
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        // Update Ferry aggregate ratings
        $newAvg = $ferry->reviews()->avg('rating');
        $newCount = $ferry->reviews()->count();

        $ferry->update([
            'rating' => $newAvg,
            'reviews_count' => $newCount,
        ]);

        return redirect()->back()->with('success', 'Review submitted successfully!');
    }
}
