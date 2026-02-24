<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RouteStop extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'address',     // ✅ FIX
        'latitude',
        'longitude',
        'stop_order',
    ];

    public function route()
    {
        return $this->belongsTo(TransportRoute::class);
    }
}
