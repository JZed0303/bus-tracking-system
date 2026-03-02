<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Trip extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'assignment_id',
        'transfer_from_trip_id',
        'trip_date',
        'scheduled_start_time',
        'scheduled_end_time',
        'actual_start_time',
        'actual_end_time',
        'direction',
        'status',
        'ended_reason',
        'incident_reported_at',
    ];

    protected $casts = [
        'trip_date'            => 'date',
        'scheduled_start_time' => 'datetime',
        'scheduled_end_time'   => 'datetime',
        'actual_start_time'    => 'datetime',
        'actual_end_time'      => 'datetime',
        'incident_reported_at' => 'datetime',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function locations()
    {
        return $this->hasMany(TripLocation::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(TripLocation::class)->latestOfMany('tracked_at');
    }

    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    public function transferFromTrip()
    {
        return $this->belongsTo(Trip::class, 'transfer_from_trip_id');
    }

    public function replacementTrips()
    {
        return $this->hasMany(Trip::class, 'transfer_from_trip_id');
    }

    public function bus()
    {
        return $this->hasOneThrough(
            Bus::class,
            Assignment::class,
            'id',            // assignments.id
            'id',            // buses.id
            'assignment_id', // trips.assignment_id
            'bus_id'         // assignments.bus_id
        );
    }

    public function route()
    {
        return $this->hasOneThrough(
            TransportRoute::class,
            Assignment::class,
            'id',
            'id',
            'assignment_id',
            'route_id'
        );
    }

    public function driver()
    {
        return $this->hasOneThrough(
            User::class,
            Assignment::class,
            'id',
            'id',
            'assignment_id',
            'driver_id'
        );
    }

    /* ================= SCOPES ================= */

    public function scopeActive($query)
    {
        return $query
            ->where('status', 'ongoing')
            ->whereHas('assignment', function ($q) {
                $q->active()
                  ->whereNotNull('bus_id')
                  ->whereNotNull('route_id');
            });
    }

    /* ================= HELPERS ================= */

    public function hasValidAssignment(): bool
    {
        return (bool) ($this->assignment && $this->assignment->isActive());
    }

    public function getDirectionLabelAttribute(): string
    {
        return match ($this->direction) {
            'pickup'  => 'Pickup',
            'dropoff' => 'Drop-off',
            default   => 'Unknown',
        };
    }

    /* ================= GIS / ANALYTICS ================= */

    public function totalDistanceKm(): float
    {
        $km = DB::table('trip_locations')
            ->where('trip_id', $this->id)
            ->selectRaw("
                ST_Length(
                    ST_MakeLine(geom ORDER BY tracked_at)::geography
                ) / 1000 AS km
            ")
            ->value('km');

        return (float) ($km ?? 0);
    }
}
