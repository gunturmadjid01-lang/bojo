<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RW extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $table = 'r_w_s';

    protected $fillable = ['dusun_id', 'nomor', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function dusun(): BelongsTo
    {
        return $this->belongsTo(Dusun::class);
    }

    public function rts(): HasMany
    {
        return $this->hasMany(RT::class, 'rw_id');
    }
}
