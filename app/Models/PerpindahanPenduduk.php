<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerpindahanPenduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;
    protected $table = 'perpindahan_penduduks';

    protected $fillable = ['kode_perpindahan', 'jenis_pindah', 'tujuan_pindah', 'tanggal_pindah', 'nomor_surat_pindah', 'alasan', 'kartu_keluarga_asal_id', 'jenis_tujuan_kk', 'kartu_keluarga_tujuan_id', 'alamat_tujuan', 'desa_tujuan', 'kecamatan_tujuan', 'kabupaten_tujuan', 'provinsi_tujuan', 'status', 'catatan', 'dibuat_oleh'];

    protected function casts(): array
    {
        return ['tanggal_pindah' => 'date'];
    }

    public function anggotaPerpindahan(): HasMany { return $this->hasMany(AnggotaPerpindahan::class); }
    public function kartuKeluargaAsal(): BelongsTo { return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_asal_id'); }
    public function kartuKeluargaTujuan(): BelongsTo { return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_tujuan_id'); }
}