<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'feature',
        'status',
        'request_time',
        'response_duration_ms',
        'tokens_used',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'request_time' => 'datetime',
        'created_at' => 'datetime',
        'metadata' => 'array',
        'response_duration_ms' => 'integer',
        'tokens_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
