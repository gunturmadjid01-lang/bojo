<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosialEkonomiKeluarga extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['kartu_keluarga_id', 'status_kepemilikan_rumah', 'kondisi_rumah', 'jenis_lantai', 'jenis_dinding', 'jenis_atap', 'sumber_air', 'sumber_listrik', 'daya_listrik', 'fasilitas_toilet', 'bahan_bakar_memasak', 'memiliki_kendaraan', 'akses_internet', 'kategori_ekonomi', 'tanggal_pendataan', 'didata_oleh'];

    protected function casts(): array
    {
        return [
            'memiliki_kendaraan' => 'boolean',
            'akses_internet' => 'boolean',
            'tanggal_pendataan' => 'date',
        ];
    }

    public function kartuKeluarga(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class);
    }

    public function didataOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'didata_oleh');
    }
}
