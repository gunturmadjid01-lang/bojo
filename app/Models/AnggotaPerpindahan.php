<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaPerpindahan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;
    protected $table = 'anggota_perpindahan';
    protected $fillable = ['perpindahan_penduduk_id', 'penduduk_id', 'kartu_keluarga_asal_id', 'kartu_keluarga_tujuan_id', 'hubungan_keluarga_tujuan'];
    public function perpindahan(): BelongsTo { return $this->belongsTo(PerpindahanPenduduk::class, 'perpindahan_penduduk_id'); }
    public function penduduk(): BelongsTo { return $this->belongsTo(Penduduk::class); }
}