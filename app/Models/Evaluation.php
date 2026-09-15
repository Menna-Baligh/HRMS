<?php

namespace App\Models;

use App\Enums\EvaluationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'period_id',
        'overall_score',
        'feedback',
        'status',
    ];

    protected $casts = [
        'overall_score' => 'float',
        'status' => EvaluationStatus::class,
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(EvaluationPeriod::class, 'period_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(EvaluationScore::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(EvaluationEvidence::class);
    }
    public function auditLogs(): HasMany
    {
        return $this->hasMany(EvaluationAuditLog::class);
    }
}
