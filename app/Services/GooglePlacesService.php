<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GooglePlacesService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('VITE_GOOGLE_MAPS_API_KEY');
    }

    /**
     * Fetch reviews and rating for a specific Place ID.
     * Caches results for 24 hours to minimize API costs.
     *
     * @return array
     */
    public function getPlaceDetails(string $placeId)
    {
        return Cache::remember("google_place_details_{$placeId}", 60 * 60 * 24, function () use ($placeId) {
            $response = Http::get('https://maps.googleapis.com/maps/api/place/details/json', [
                'place_id' => $placeId,
                'fields' => 'name,rating,user_ratings_total,reviews',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return $response->json('result');
            }

            return null;
        });
    }

    /**
     * Map Google Reviews to our internal structure.
     */
    public function formatReviews(array $googleReviews)
    {
        return array_map(function ($review) {
            return [
                'id' => 'google_'.md5($review['author_name'].$review['time']),
                'user' => [
                    'name' => $review['author_name'],
                    'avatar' => $review['profile_photo_url'] ?? null,
                ],
                'rating' => $review['rating'],
                'comment' => $review['text'],
                'created_at' => date('Y-m-d H:i:s', $review['time']),
                'source' => 'Google Maps',
            ];
        }, $googleReviews);
    }
}
