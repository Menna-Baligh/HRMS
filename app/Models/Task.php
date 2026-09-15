<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'deadline',
        'created_by',
        'progress',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'priority' => TaskPriority::class,
        'status' => TaskStatus::class,
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Employees assigned to this task.
     */
    public function assignments()
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * Task activity history.
     */
    public function activities()
    {
        return $this->hasMany(TaskActivity::class);
    }
}
