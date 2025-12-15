<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ferry extends Model
{
    protected $fillable = ['name', 'capacity', 'operator'];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
