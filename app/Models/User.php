<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'provider',
        'provider_id',
        'role',
        'manager_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    // ─── JWTSubject ──────────────────────────────────────────────────────────

    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return ['role' => $this->role->value];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    /** The manager this user reports to. */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** All employees directly managed by this user. */
    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /** All leave requests submitted by this user. */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** Leave balance records for this user. */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isHR(): bool
    {
        return $this->role === UserRole::HR;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    public function isHrOrAbove(): bool
    {
        return $this->role->isHrOrAbove();
    }

    public function isManagerOrAbove(): bool
    {
        return $this->role->isManagerOrAbove();
    }
}
