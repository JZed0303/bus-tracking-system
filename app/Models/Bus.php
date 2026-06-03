<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

use App\Models\Trip;
use App\Models\Assignment;
use App\Models\BusGps;
use App\Models\ChatParticipant;
use App\Models\ChatMessage;
use App\Models\ChatThread;

class Bus extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'plate_number',
        'bus_code',
        'capacity',
        'brand_model',
        'status',
        'photo',
        'last_seen_at', // ✅ make sure this exists in your buses table
    ];

    protected $hidden = [
        'bus_code',
    ];

    protected $auditExclude = [
        'bus_code',
    ];

    protected $casts = [
        'capacity'     => 'integer',
        'last_seen_at' => 'datetime',
    ];

    protected $appends = [
        'photo_url',
        'is_online',
        'presence',
    ];

    /* ================= RELATIONSHIPS ================= */

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'bus_id', 'id');
    }

    /**
     * If you have Assignment::scopeActive() then you can keep this.
     * Otherwise, replace ->active() with ->where('status','active') etc.
     */
    public function activeAssignment()
    {
        return $this->hasOne(Assignment::class, 'bus_id', 'id')->active();
    }

    public function chatParticipants()
    {
        return $this->morphMany(ChatParticipant::class, 'participant', 'participant_type', 'participant_id');
    }

    public function chatMessages()
    {
        return $this->morphMany(ChatMessage::class, 'sender', 'sender_type', 'sender_id');
    }

    public function gps()
    {
        return $this->hasMany(BusGps::class, 'bus_id', 'id');
    }

    public function latestGps()
    {
        return $this->hasOne(BusGps::class, 'bus_id', 'id')->latestOfMany('tracked_at');
    }

    /**
     * All trips through assignments (history).
     */
    public function trips()
    {
        return $this->hasManyThrough(
            Trip::class,
            Assignment::class,
            'bus_id',        // assignments.bus_id -> buses.id
            'assignment_id', // trips.assignment_id -> assignments.id
            'id',            // buses.id
            'id'             // assignments.id
        );
    }

    /**
     * ✅ Active trip relationship (SAFE for eager loading)
     *
     * IMPORTANT: Update the WHERE conditions to match your schema:
     * - assignments.status might be 'active' / 'ongoing' etc.
     * - trips.actual_end_time might be 'ended_at' or 'completed_at'
     */
    public function activeTrip()
    {
        return $this->hasOneThrough(
                Trip::class,
                Assignment::class,
                'bus_id',        // assignments.bus_id -> buses.id
                'assignment_id', // trips.assignment_id -> assignments.id
                'id',            // buses.id
                'id'             // assignments.id
            )
            // ✅ constrain to active assignment
            ->where('assignments.status', 'active') // change if your column differs

            // ✅ constrain to ongoing trip
            ->whereNull('trips.actual_end_time')    // change if your column differs

            // ✅ pick latest start
            ->orderByDesc('trips.actual_start_time'); // change if your column differs
    }

    /**
     * Current/last known location:
     * - Prefer activeTrip.latestLocation (if exists)
     * - Else fallback to latestGps
     */
    public function currentLocation()
    {
        $trip = $this->activeTrip; // relationship property (supports eager load)

        if ($trip && $trip->latestLocation) {
            return $trip->latestLocation;
        }

        return $this->latestGps;
    }

    public function supportThread()
    {
        return $this->hasOne(ChatThread::class, 'context_id', 'id')
            ->where('context_type', 'bus_support');
    }

    /* ================= PHOTO HELPERS ================= */

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo && Storage::disk('public')->exists('buses/' . $this->photo)) {
            return asset('storage/buses/' . $this->photo);
        }

        return asset('build/images/bus-placeholder.png');
    }

    /* ================= STATUS HELPERS ================= */

    public function getIsOnlineAttribute(): bool
    {
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

    public function markActive(): void
    {
        $this->update([
            'status'       => 'active',
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        // "offline" is a runtime presence state, not a persisted bus lifecycle status.
        $this->forceFill([
            'last_seen_at' => now()->subMinutes(10),
        ])->saveQuietly();
    }

    public function isOnline(): bool
    {
        return $this->getIsOnlineAttribute();
    }

    /* ================= SCOPES ================= */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
