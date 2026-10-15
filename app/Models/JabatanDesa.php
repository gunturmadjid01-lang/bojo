<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JabatanDesa extends Model
{
    protected $fillable = ['kode', 'nama', 'tingkat', 'aktif', 'bawaan_sistem'];

    protected $casts = [
        'aktif' => 'boolean',
        'bawaan_sistem' => 'boolean',
    ];

    public function isSystemDefault(): bool
    {
        return (bool) $this->bawaan_sistem;
    }

    public function penugasan(): HasMany { return $this->hasMany(PenugasanPerangkat::class); }
}
