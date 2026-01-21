<?php

namespace Database\Seeders;

use App\Models\Ferry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class FerryDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonAmenities = ['Air Conditioning', 'Life Jackets', 'Indoor Seating', 'Restrooms', 'Luggage Rack'];
        $luxuryAmenities = array_merge($commonAmenities, ['TV Entertainment', 'Snack Kiosk', 'VIP Section']);
        $speedboatAmenities = ['Life Jackets', 'Outdoor Deck', 'Canopy Cover'];

        $ferries = Ferry::all();

        foreach ($ferries as $ferry) {
            $type = 'standard';
            $price = 20.00;
            $length = '100 ft';
            $amenities = $commonAmenities;
            $desc = 'Reliable ferry service ensuring a smooth journey.';

            // Categorize based on Name/Operator
            $image = null;
            $ticketType = 'Walk-in / Counter';

            if (str_contains($ferry->name, 'Langkawi') || str_contains($ferry->name, 'Dragon') || str_contains($ferry->name, 'MyFerry')) {
                $type = 'large';
                $price = 35.00;
                $length = '120 ft';
                $amenities = $luxuryAmenities;
                $desc = 'Experience comfort on this high-speed ferry to Langkawi. Features air-conditioned cabins and onboard entertainment.';
                $image = 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80';
                $ticketType = 'Online Booking';
            } elseif (str_contains($ferry->name, 'Penang') || str_contains($ferry->name, 'Teluk')) {
                $type = 'catamaran';
                $price = 10.00;
                $length = '80 ft';
                $amenities = $commonAmenities;
                $desc = 'Iconic Penang ferry service offering scenic views of the harbor and heritage sites.';
                $image = 'https://images.unsplash.com/photo-1559599101-f09722fb4948?q=80&w=2069&auto=format&fit=crop';
                $ticketType = 'Both Available';
            } elseif (str_contains($ferry->operator, 'Bluewater') || str_contains($ferry->operator, 'Cataferry')) {
                $type = 'medium';
                $price = 60.00;
                $length = '90 ft';
                $amenities = $luxuryAmenities;
                $desc = 'Your gateway to Tioman Island. Enjoy a steady ride with spacious seating and sundeck access.';
                $image = 'https://images.unsplash.com/photo-1502209877429-d7c6d98652d0?q=80&w=2070&auto=format&fit=crop';
                $ticketType = 'Online Booking';
            } elseif (str_contains($ferry->name, 'Indomal') || str_contains($ferry->name, 'Putri') || str_contains($ferry->name, 'Tunas')) {
                $type = 'international';
                $price = 120.00;
                $length = '150 ft';
                $amenities = $luxuryAmenities;
                $desc = 'International class fast ferry connecting Malaysia and Indonesia with premium service standards.';
                $image = 'https://images.unsplash.com/photo-1534951474654-8845f37ca707?q=80&w=2070&auto=format&fit=crop';
                $ticketType = 'Online Booking';
            } elseif (str_contains($ferry->operator, 'Perhentian') || str_contains($ferry->operator, 'Redang') || str_contains($ferry->operator, 'Kapas')) {
                $type = 'speedboat';
                $price = 45.00;
                $length = '38 ft';
                $amenities = $speedboatAmenities;
                $desc = 'Fast and thrilling speedboat transfer directly to your island resort beach.';
                $image = 'https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?q=80&w=2087&auto=format&fit=crop';
                $ticketType = 'Walk-in / Counter';
            } else {
                // Pangkor / Others
                $image = 'https://images.unsplash.com/photo-1577987820067-27476e730702?q=80&w=2070&auto=format&fit=crop';
                $ticketType = 'Walk-in / Counter';
            }

            // Update
            $ferry->update([
                'description' => $ferry->description ?? $desc,
                'price' => $ferry->price ?? $price,
                'length_ft' => $ferry->length_ft ?? $length,
                'rating' => $ferry->rating ?? fake()->randomFloat(1, 4.0, 4.9),
                'reviews_count' => $ferry->reviews_count ?? fake()->numberBetween(50, 800),
                'amenities' => $ferry->amenities ?? Arr::random($amenities, min(4, count($amenities))),
                'image_path' => $ferry->image_path ?? $image,
                'booking_url' => $ferry->booking_url ?? 'https://www.easybook.com/en-my/ferry',
                'ticket_type' => $ferry->ticket_type ?? $ticketType
            ]);
        }
    }
}
