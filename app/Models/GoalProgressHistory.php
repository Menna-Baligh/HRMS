<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalProgressHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'updated_by',
        'previous_value',
        'new_value',
        'note',
    ];

    protected $casts = [
        'previous_value' => 'float',
        'new_value' => 'float',
    ];

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
