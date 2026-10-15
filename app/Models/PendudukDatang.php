<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PendudukDatang extends KedatanganPenduduk
{
    protected $table = 'kedatangan_penduduks';

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class, 'penduduk_id');
    }

    public function kartaKeluargaTujuan(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_tujuan_id');
    }

    public function anggotaKedatangan(): HasMany
    {
        return $this->hasMany(AnggotaKedatangan::class, 'kedatangan_penduduk_id');
    }
}
