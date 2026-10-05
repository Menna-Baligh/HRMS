<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_period',
        'is_popular',
        'features',
        'button_text',
        'button_link',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_popular' => 'boolean',
        'features' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];
}
