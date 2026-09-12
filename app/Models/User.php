<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'api';

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
        'email_verified_at',
        'role',
        'provider',
        'provider_id',
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

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        $roleValue = $this->role instanceof \BackedEnum ? $this->role->value : (string) $this->role;

        return ['role' => $roleValue];
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

    /** The employee profile associated with this user. */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isOwner(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Owner'))
            || $this->role === UserRole::Owner
            || $this->role === 'Owner';
    }

    public function isHR(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('HR'))
            || $this->role === UserRole::HR
            || $this->role === 'HR';
    }

    public function isManager(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Manager'))
            || $this->role === UserRole::Manager
            || $this->role === 'Manager';
    }

    public function isEmployee(): bool
    {
        return (method_exists($this, 'hasRole') && $this->hasRole('Employee'))
            || $this->role === UserRole::Employee
            || $this->role === 'Employee';
    }

    public function isHrOrAbove(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isHrOrAbove();
        }

        return in_array($this->role, ['Owner', 'HR', UserRole::Owner, UserRole::HR], true);
    }

    public function isManagerOrAbove(): bool
    {
        if ($this->role instanceof UserRole) {
            return $this->role->isManagerOrAbove();
        }

        return in_array($this->role, ['Owner', 'HR', 'Manager', UserRole::Owner, UserRole::HR, UserRole::Manager], true);
    }
}
