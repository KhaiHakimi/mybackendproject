<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'ferry_id',
        'origin_port_id',
        'destination_port_id',
        'departure_time',
        'arrival_time',
        'price',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
    ];

    public function ferry()
    {
        return $this->belongsTo(Ferry::class);
    }

    public function origin()
    {
        return $this->belongsTo(Port::class, 'origin_port_id');
    }

    public function destination()
    {
        return $this->belongsTo(Port::class, 'destination_port_id');
    }
}
