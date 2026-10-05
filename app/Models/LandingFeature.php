<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingFeature extends Model
{
    protected $fillable = [
        'icon',
        'title',
        'description',
        'order',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];
}
