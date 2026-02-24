<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusGps extends Model
{
    protected $table = 'bus_gps'; // change if different

    protected $fillable = [
        'bus_id',
        'latitude',
        'longitude',
        'tracked_at',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
