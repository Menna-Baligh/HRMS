<?php

namespace App\Models;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    protected $fillable = [
        'task_id',
        'employee_id',
        'assigned_by',
        'assigned_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];


    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Employee assigned to the task.
     */
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * User who assigned the task.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
