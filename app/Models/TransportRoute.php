<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class TransportRoute extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $table = 'routes';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'company_id',
        'start_location',
        'end_location',
        'start_lat',
        'start_lng',
        'end_lat',
        'end_lng',
        'description',
        'status',
    ];

    /* ================= RELATIONSHIPS ================= */

    /**
     * Route owner (nullable = shared route)
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }


   public function activeTrips()
{
    return $this->hasManyThrough(
        Trip::class,
        Assignment::class,
        'route_id',        // assignments.route_id
        'assignment_id',   // trips.assignment_id
        'id',              // routes.id
        'id'               // assignments.id
    )->where('trips.status', 'active');
}
    /**
     * Ordered pickup/drop-off stops
     */
    public function stops()
    {
        return $this->hasMany(RouteStop::class, 'route_id')
            ->orderBy('stop_order');
    }

    /**
     * Driver–Bus–Company assignments using this route
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'route_id');
    }

    /**
     * Trips generated from assignments
     */
    public function trips()
    {
        return $this->hasManyThrough(
            Trip::class,
            Assignment::class,
            'route_id',        // assignments.route_id
            'assignment_id'    // trips.assignment_id
        );
    }

    /* ================= SCOPES ================= */

    /**
     * Only active routes
     */
    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }
}
