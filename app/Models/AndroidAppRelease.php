<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AndroidAppRelease extends Model
{
    protected $fillable = [
        'version', 'version_code', 'file_path', 'file_size', 'sha256',
        'release_notes', 'is_latest', 'published_at', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'version_code' => 'integer',
            'file_size' => 'integer',
            'is_latest' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
