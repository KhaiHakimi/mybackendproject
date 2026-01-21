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
    /**
     * Display the specified resource for public view.
     */
    public function publicShow(Ferry $ferry, \App\Services\GooglePlacesService $googlePlaces)
    {
        $ferry->load(['schedules']);
        // Note: We no longer load local 'reviews' relationship as we use external sources.

        $googleReviews = [];
        $ratingDistribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        // Fetch external reviews if linked
        $placeDetails = null;

        if ($ferry->google_place_id) {
            // Cache external data for 24 hours (1440 mins) to avoid hitting API limit and improve speed
            $placeDetails = Cache::remember("ferry.{$ferry->id}.google_details", 1440 * 60, function () use ($ferry, $googlePlaces) {
                return $googlePlaces->getPlaceDetails($ferry->google_place_id);
            });

            if ($placeDetails) {
                // Update local cache of rating (optional, but good for list views)
                // We only update if it has changed significantly to avoid DB thrashing
                if ($ferry->rating != ($placeDetails['rating'] ?? $ferry->rating)) {
                    $ferry->update([
                        'rating' => $placeDetails['rating'] ?? $ferry->rating,
                        'reviews_count' => $placeDetails['user_ratings_total'] ?? $ferry->reviews_count,
                    ]);
                    // If we updated the ferry, invalidate list cache
                    Cache::forget('ferries.all');
                }

                if (isset($placeDetails['reviews'])) {
                    $googleReviews = $googlePlaces->formatReviews($placeDetails['reviews']);

                    // Approximate distribution from available reviews (limit of 5 usually)
                    foreach ($placeDetails['reviews'] as $r) {
                        $rating = round($r['rating']);
                        if (isset($ratingDistribution[$rating])) {
                            $ratingDistribution[$rating]++;
                        }
                    }
                }
            }
        }

        // Pass 'reviews' as a merged list (currently just Google, but extensible)
        $ferry->reviews = $googleReviews;

        return Inertia::render('Ferries/PublicShow', [
            'ferry' => $ferry,
            'ratingDistribution' => $ratingDistribution,
            'externalSource' => true, // Flag for frontend to know these are external
        ]);
    }

    /**
     * Store a new review for a ferry.
     * DEPRECATED: Reviews are now handled via external platforms (Google Maps).
     */
    public function storeReview(Request $request, Ferry $ferry)
    {
        return redirect()->back()->with('error', 'Review submission is disabled. Please review us on Google Maps.');
    }
}
