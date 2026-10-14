<?php

namespace App\Models;

use Database\Factories\FileLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileLink extends Model
{
    /** @use HasFactory<FileLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'file_id',
        'linkable_type',
        'linkable_id',
        'note',
    ];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
