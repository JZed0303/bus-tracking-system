<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeQrCode extends Model
{
    use HasFactory;

    /**
     * Explicit table name
     * (because your migration uses `employee_qrcodes`)
     */
    protected $table = 'employee_qrcodes';

    /**
     * You are using custom timestamps
     */
    public $timestamps = false;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'employee_id',
        'qr_token',
        'is_active',
        'generated_at',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'is_active'    => 'boolean',
        'generated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function getStatusAttribute(): string
{
    if (!$this->is_active) {
        return 'revoked';
    }

    if ($this->generated_at < now()->subHours(24)) {
        return 'expired';
    }

    return 'valid';
}


public function getExpiresAtAttribute()
{
    return $this->generated_at?->copy()->addHours(24);
}

}
