<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Employee extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'user_id',
        'company_id',
        'employee_code',
        'department',
        'position',
        'photo_path',
        'status',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function qrcode()
    {
        return $this->hasOne(EmployeeQrCode::class)
            ->where('is_active', true);
    }

    // app/Models/Employee.php

public function latestQr()
{
    return $this->hasOne(EmployeeQrCode::class)
        ->latestOfMany();
}

    // ✅ ADD THIS
    public function checkins()
    {
        return $this->hasMany(Checkin::class);
    }

    public function pickupStop()
{
    return $this->hasOne(EmployeeRouteStop::class)
        ->where('type', 'pickup');
}

 public function scopeActive($query)
    {
        return $query->where('status', 'active');
        // OR ->where('is_active', 1)
    }
}
