<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_name',
        'path',
        'mime_type',
        'size',
        'fileable_type',
        'fileable_id',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
