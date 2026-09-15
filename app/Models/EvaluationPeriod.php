<?php

namespace App\Models;

use App\Enums\EvaluationPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'status' => EvaluationPeriodStatus::class,
    ];

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'period_id');
    }
}
