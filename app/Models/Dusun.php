<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dusun extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['desa_id', 'kode', 'nama', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function penugasanPerangkat(): HasMany { return $this->hasMany(PenugasanPerangkat::class); }

    public function rws(): HasMany
    {
        return $this->hasMany(RW::class);
    }
}
