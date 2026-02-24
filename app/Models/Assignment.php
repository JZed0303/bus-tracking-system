<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'bus_id',
        'company_id',
        'route_id',
        'effective_from',
        'effective_to',
        'status',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to'   => 'date',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function getStartDateAttribute()
{
    return $this->effective_from;
}
    public function route()
    {
        return $this->belongsTo(TransportRoute::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function isActive(): bool
{
    $today = Carbon::today('Asia/Manila');

    return $this->status === 'active'
        && $this->effective_from <= $today
        && (
            is_null($this->effective_to)
            || $this->effective_to >= $today
        );
}


public function isUpcoming(): bool
{
    $today = Carbon::today('Asia/Manila');

    return $this->status === 'active'
        && $this->effective_from > $today;
}

public function isExpired(): bool
{
    $today = Carbon::today('Asia/Manila');

    return $this->effective_to
        && $this->effective_to < $today;
}
public function scopeActive($query)
{
    $today = now('Asia/Manila')->toDateString();

    return $query
        ->where('status', 'active')
        ->where('effective_from', '<=', $today)
        ->where(function ($q) use ($today) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $today);
        });
}

}
