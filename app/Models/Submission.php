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
        'user_id',
        'note',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'status' => SubmissionStatus::class,
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the task associated with the submission.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who created the submission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get submission attachments.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class);
    }

    /**
     * Get submission reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class);
    }

    /**
     * Get files attached to the submission.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
