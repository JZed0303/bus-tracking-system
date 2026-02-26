<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
<<<<<<< HEAD
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
=======
>>>>>>> origin/IBTS-v1

class Driver extends Model implements Auditable
{
    use HasFactory, AuditableTrait;

    protected $fillable = [
        'user_id',
        'company_id',
        'license_number',
        'phone',
        'photo_path',
        'status',
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): string
    {
        if (!empty($this->photo_path)) {
            // Build URL from current app host (avoids localhost/bus.local mismatch).
            return asset('storage/' . ltrim($this->photo_path, '/'));
        }

        return asset('build/images/user-placeholder.png');
    }

    /* ================= RELATIONSHIPS ================= */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function currentAssignment()
    {
        return $this->hasOne(Assignment::class)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')
                  ->orWhereDate('effective_to', '>=', now());
            })
            ->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function trips()
    {
        return $this->hasManyThrough(
            Trip::class,
            Assignment::class,
            'driver_id',
            'assignment_id'
        );
    }
}
