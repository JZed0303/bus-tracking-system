<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'company_id',
        'route_id',
        'bus_id',
        'schedule_date',
        'shift_name',
        'expected_pickup_time',
        'expected_dropoff_time',
        'status',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'expected_pickup_time' => 'datetime:H:i',
        'expected_dropoff_time' => 'datetime:H:i',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function route()
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    /* ================= SCOPES ================= */

    public function scopeToday($query)
    {
        return $query->whereDate('schedule_date', today());
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
}
