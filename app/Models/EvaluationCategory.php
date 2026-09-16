<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'max_score',
        'weight',
    ];

    protected $casts = [
        'max_score' => 'float',
        'weight' => 'float',
    ];
}
