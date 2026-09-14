<?php


namespace App\Models;

use App\Enums\GoalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'title',
        'description',
        'target_value',
        'current_value',
        'target_date',
        'status',
    ];

    protected $casts = [
        'target_value' => 'float',
        'current_value' => 'float',
        'target_date' => 'date:Y-m-d',
        'status' => GoalStatus::class,
    ];

    protected $appends = ['progress_percentage'];

    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_value <= 0) {
            return 0;
        }

        $percentage = ($this->current_value / $this->target_value) * 100;

        return min(100, round($percentage, 2));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(GoalProgressHistory::class)->latest();
    }
}