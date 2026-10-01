<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingSection extends Model
{
    protected $fillable = [
        'section_key',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];
}
