<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripEmployeeTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_trip_id',
        'to_trip_id',
        'employee_id',
        'created_by_bus_id',
        'status',
        'reason',
        'transferred_at',
        'confirmed_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function fromTrip()
    {
        return $this->belongsTo(Trip::class, 'from_trip_id');
    }

    public function toTrip()
    {
        return $this->belongsTo(Trip::class, 'to_trip_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdByBus()
    {
        return $this->belongsTo(Bus::class, 'created_by_bus_id');
    }
}
