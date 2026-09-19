<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Submission extends Model
{
    protected $fillable = [
        'task_id',
        'employee_id',
        'note',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'submitted_at' => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
