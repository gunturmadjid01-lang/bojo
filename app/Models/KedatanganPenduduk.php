<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KedatanganPenduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $table = 'kedatangan_penduduks';

    protected $fillable = [
        'jenis_kedatangan',
        'tanggal_datang',
        'nomor_surat_pindah',
        'alamat_sebelumnya',
        'desa_sebelumnya',
        'kecamatan_sebelumnya',
        'kabupaten_sebelumnya',
        'provinsi_sebelumnya',
        'jenis_tujuan_kk',
        'kartu_keluarga_tujuan_id',
        'penduduk_id',
        'status',
        'alasan',
        'catatan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_datang' => 'date',
        ];
    }

    public function kartuKeluargaTujuan(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_tujuan_id');
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function anggotaKedatangan(): HasMany
    {
        return $this->hasMany(AnggotaKedatangan::class, 'kedatangan_penduduk_id');
    }
}
