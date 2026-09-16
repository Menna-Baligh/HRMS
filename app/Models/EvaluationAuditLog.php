<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluationAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id',
        'user_id',
        'action',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
