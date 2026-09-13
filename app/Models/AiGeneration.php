<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_employee_id',
        'feature',
        'version',
        'context_reference',
        'regenerated_from_id',
        'request_envelope',
        'output_payload',
        'status',
    ];

    protected $casts = [
        'request_envelope' => 'array',
        'output_payload' => 'array',
        'version' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'target_employee_id');
    }

    public function regeneratedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'regenerated_from_id');
    }

    public function regenerations(): HasMany
    {
        return $this->hasMany(self::class, 'regenerated_from_id');
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(AiFeedback::class);
    }
}
