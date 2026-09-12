<?php

namespace App\Models;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaskActivity extends Model
{
    protected $fillable = [
        'task_id',
        'employee_id',
        'action',
        'old_value',
        'new_value',
        'description',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * User who performed the activity.
     */
    public function employee()
    {
        return $this->belongsTo(User::class);
    }
}
