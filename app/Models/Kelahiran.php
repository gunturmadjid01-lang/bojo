<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kelahiran extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'kartu_keluarga_id', 'ayah_id', 'ibu_id', 'nomor_akta_kelahiran', 'tanggal_akta_kelahiran', 'tempat_lahir', 'tanggal_lahir', 'waktu_lahir', 'anak_ke', 'berat_lahir', 'panjang_lahir', 'tanggal_dilaporkan', 'catatan', 'dibuat_oleh'];

    protected function casts(): array
    {
        return [
            'tanggal_akta_kelahiran' => 'date',
            'tanggal_lahir' => 'date',
            'tanggal_dilaporkan' => 'date',
        ];
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class);
    }

    public function ayah(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class, 'ayah_id');
    }

    public function ibu(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class, 'ibu_id');
    }
}
