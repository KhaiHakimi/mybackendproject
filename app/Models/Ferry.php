<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ferry extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'operator',
        'image_path',
        'rating',
        'reviews_count',
        'length_ft',
        'amenities',
        'price',
        'description',
        'booking_url',
        'ticket_type',
        'google_place_id',
    ];

    protected $casts = [
        'amenities' => 'array',
        'rating' => 'decimal:1',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
