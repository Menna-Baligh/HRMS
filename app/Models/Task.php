<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'progress',
        'created_by',
        'deadline',
    ];

    protected $casts = [
        'priority' => TaskPriority::class,
        'status' => TaskStatus::class,
        'deadline' => 'datetime',
        'progress' => 'integer',
    ];

    /**
     * User who created the task.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Employees assigned to the task.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Task activity history.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(TaskActivity::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
