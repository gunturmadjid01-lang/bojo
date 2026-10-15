<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaKartuKeluarga extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['kartu_keluarga_id', 'penduduk_id', 'hubungan_keluarga', 'tanggal_masuk', 'tanggal_keluar', 'aktif', 'alasan_keluar'];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'aktif' => 'boolean',
        ];
    }

    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class);
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }
}
