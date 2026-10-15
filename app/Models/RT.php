<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RT extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $table = 'r_t_s';

    protected $fillable = ['rw_id', 'nomor', 'aktif'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(RW::class);
    }
}
