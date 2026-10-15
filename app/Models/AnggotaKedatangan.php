<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaKedatangan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $table = 'anggota_kedatangan';

    protected $fillable = [
        'kedatangan_penduduk_id',
        'penduduk_id',
        'hubungan_keluarga',
    ];

    public function kedatanganPenduduk(): BelongsTo
    {
        return $this->belongsTo(KedatanganPenduduk::class, 'kedatangan_penduduk_id');
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }
}
