<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Employee;
use App\Models\Trip;
use App\Models\User;
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
        'client_scan_id',
        'is_offline',
        'synced_at',
        'voided_at',
        'void_reason',
        'voided_by_user_id',
    ];

    protected $casts = [
        'scan_time' => 'datetime',
        'is_offline' => 'boolean',
        'synced_at' => 'datetime',
        'voided_at' => 'datetime',
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

    // VOID FLOW: track which user voided this scan for audit/reporting.
    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by_user_id');
    }

    /* ================= ACCESSORS ================= */

    public function getCheckedInAtAttribute()
    {
        return $this->scan_time;
    }

}
