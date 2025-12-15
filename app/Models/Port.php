<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $fillable = ['name', 'location', 'latitude', 'longitude'];

    public function departures()
    {
        return $this->hasMany(Schedule::class, 'origin_port_id');
    }

    public function arrivals()
    {
        return $this->hasMany(Schedule::class, 'destination_port_id');
    }

    public function weatherData()
    {
        return $this->hasMany(WeatherData::class);
    }
}
