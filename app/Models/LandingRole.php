<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingRole extends Model
{
    protected $fillable = [
        'role_name',
        'title',
        'description',
        'features',
        'order',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];
}
