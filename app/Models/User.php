<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\UserDeniedPermission;
use Illuminate\Support\Facades\Gate;
use App\Models\ChatThread;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /* ================= BASIC CONFIG ================= */

    protected $fillable = [
    'first_name',
    'middle_name',
    'last_name',
    'address',
    'email',
     'role',
    'password',
    'status',
    'last_login_at',
    'company_id', // ✅ ADD THIS
];


protected $casts = [
  'last_login_at' => 'datetime',
  'last_seen_at'  => 'datetime', // ✅ add
  'email_verified_at' => 'datetime',
  'denied_permissions' => 'array',
];

protected $appends = [
  'full_name',
  'is_online',     // ✅ add
  'presence',      // ✅ add: online/offline/away
];
    protected $hidden = [
        'password',
        'remember_token',
    ];



    public function chatParticipants()
{
    return $this->morphMany(ChatParticipant::class, 'participant', 'participant_type', 'participant_id');
}

public function chatMessages()
{
    return $this->morphMany(ChatMessage::class, 'sender', 'sender_type', 'sender_id');
}


public function getIsOnlineAttribute(): bool
{
  // Consider online if seen within last 3 minutes
  if (!$this->last_seen_at) return false;

  return $this->last_seen_at->gt(now()->subMinutes(3));
}

public function getPresenceAttribute(): string
{
  if (!$this->last_seen_at) return 'offline';

  if ($this->last_seen_at->gt(now()->subMinutes(3))) return 'online';
  if ($this->last_seen_at->gt(now()->subMinutes(15))) return 'away';

  return 'offline';
}

    /* ================= RELATIONSHIPS ================= */

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function company()
{
    return $this->belongsTo(Company::class);
}

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /* ================= ACCESSORS ================= */

    public function getFullNameAttribute(): string
    {
        return trim(
            "{$this->first_name} " .
            ($this->middle_name ? "{$this->middle_name} " : '') .
            "{$this->last_name}"
        );
    }

    public function chatThreads()
{
    return $this->belongsToMany(ChatThread::class, 'chat_participants', 'user_id', 'thread_id')
        ->withPivot(['role', 'last_read_message_id'])
        ->withTimestamps();
}



    /* ================= SCOPES ================= */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
/* ================= ROLE HELPERS ================= */

public function isSuperAdmin(): bool
{
    return $this->hasRole('super_admin');
}

public function isSystemAdmin(): bool
{
    return $this->hasAnyRole(['super_admin', 'admin']);
}

public function isCompanyAdmin(): bool
{
    return $this->hasRole('company_admin');
}

public function isDriver(): bool
{
    return $this->hasRole('driver');
}

public function isEmployee(): bool
{
    return $this->hasRole('employee');
}

/* ================= AREA HELPERS ================= */

public function isCompanyUser(): bool
{
    return $this->isCompanyAdmin();
}

public function isAdminUser(): bool
{
    return $this->isSystemAdmin();
}

/* ================= ROUTE HELPERS ================= */

public function dashboardRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.dashboard')
        : route('admin.dashboard');
}

public function tripsRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.trips.today')
        : route('admin.trips.today');
}

public function busRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.buses.index')
        : route('admin.buses.index');
}

public function liveTrackingRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.live-tracking')
        : route('admin.live-map');
}

public function reportsRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.reports.index')
        : route('admin.reports.index');
}

public function profileRoute(): string
{
    return $this->isCompanyUser()
        ? route('company.profile.show')
        : route('admin.profile.show');
}

public function deniedPermissions()
{
    return $this->hasMany(UserDeniedPermission::class);
}


public function isPermissionDenied(string $permission): bool
{
    return $this->deniedPermissions()
        ->where('permission', $permission)
        ->exists();
}

public function denyPermission(string $permission): void
{
    $this->deniedPermissions()->firstOrCreate([
        'permission' => $permission,
    ]);
}

public function allowPermission(string $permission): void
{
    $this->deniedPermissions()
        ->where('permission', $permission)
        ->delete();
}

/**
 * Override permission resolution so USER denies override ROLE permissions
 */
public function can($ability, $arguments = []): bool
{
    // 🔴 1. Explicit user-level DENY always wins
    if ($this->isPermissionDenied($ability)) {
        return false;
    }

    // 🟢 2. Super admin bypass
    if ($this->hasRole('super_admin')) {
        return true;
    }

    // 🟡 3. Fallback to Spatie role/permission logic
    return parent::can($ability, $arguments);
}

public function permissionState(string $permission): string
{
    if ($this->isPermissionDenied($permission)) {
        return 'denied';
    }

    if ($this->hasDirectPermission($permission)) {
        return 'allowed';
    }

    if ($this->hasPermissionTo($permission)) {
        return 'inherited';
    }

    return 'none';


}


public function canByRole(string $ability, array $arguments = []): bool
{
    return $this->can($ability, $arguments);
}
}



// User::where('email', 'johnzedrickiglesia@gmail.com')->first();
//$user = User::find(16);
//$user = User::find(16);
//$qr = $user->employee?->qrcode;
