<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatDomisiliKartuKeluarga extends Model
{
    protected $table = 'riwayat_domisili_kartu_keluarga';
    protected $fillable = ['kartu_keluarga_id', 'desa_id', 'dusun_id', 'rw_id', 'rt_id', 'alamat', 'kode_pos', 'tanggal_mulai', 'tanggal_selesai', 'alasan_perubahan', 'perpindahan_penduduk_id', 'dibuat_oleh'];
    protected function casts(): array { return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date']; }
    public function kartuKeluarga(): BelongsTo { return $this->belongsTo(KartuKeluarga::class); }
    public function perpindahan(): BelongsTo { return $this->belongsTo(PerpindahanPenduduk::class, 'perpindahan_penduduk_id'); }
}