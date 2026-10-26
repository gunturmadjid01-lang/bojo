<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendudukPindah extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = [
        'penduduk_id',
        'jenis_tujuan_kk',
        'kartu_keluarga_tujuan_id',
        'hubungan_keluarga',
        'tanggal_pindah',
        'alamat_tujuan',
        'desa_tujuan',
        'kecamatan_tujuan',
        'kabupaten_tujuan',
        'provinsi_tujuan',
        'nomor_surat_pindah',
        'alasan',
        'status',
        'catatan',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pindah' => 'date',
        ];
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function kartuKeluargaTujuan(): BelongsTo
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_tujuan_id');
    }
}
