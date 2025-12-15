<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherData extends Model
{
    protected $fillable = [
        'port_id', 
        'recorded_at', 
        'wind_speed', 
        'wave_height', 
        'visibility', 
        'tide_level', 
        'precipitation', 
        'precipitation', 
        'risk_score',
        'risk_status',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function port()
    {
        return $this->belongsTo(Port::class);
    }
}
