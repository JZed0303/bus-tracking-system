<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Models\Trip;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Checkin extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'trip_id',
        'employee_id',
        'scan_type',
        'scan_time',
        'scan_lat',
        'scan_lng',
        'scanned_by_employee_id',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
    ];

    protected $appends = ['checked_in_at'];

    /* ================= RELATIONSHIPS ================= */

    public function scannedByEmployee()
{
    return $this->belongsTo(Employee::class, 'scanned_by_employee_id');
}

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /* ================= ACCESSORS ================= */

    public function getCheckedInAtAttribute()
    {
        return $this->scan_time;
    }

}
