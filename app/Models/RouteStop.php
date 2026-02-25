<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class RouteStop extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

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
