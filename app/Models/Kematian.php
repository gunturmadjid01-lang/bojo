<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kematian extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'tanggal_meninggal', 'tempat_meninggal', 'penyebab_meninggal', 'nomor_surat_kematian', 'tanggal_dilaporkan', 'catatan', 'dibuat_oleh'];

    protected function casts(): array
    {
        return [
            'tanggal_meninggal' => 'date',
            'tanggal_dilaporkan' => 'date',
        ];
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }
}
