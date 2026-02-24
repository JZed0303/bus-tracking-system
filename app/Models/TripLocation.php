<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripLocation extends Model
{
    use HasFactory;

    protected $table = 'trip_locations';

    /**
     * IMPORTANT: do NOT define $guarded
     */
    protected $fillable = [
        'trip_id',
        'bus_id',
        'driver_id',
        'latitude',
        'longitude',
        'speed',
        'tracked_at',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'latitude'   => 'float',
        'longitude'  => 'float',
        'speed'      => 'float',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
