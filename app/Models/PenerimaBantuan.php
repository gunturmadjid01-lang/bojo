<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenerimaBantuan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['program_bantuan_id', 'penduduk_id', 'kartu_keluarga_id', 'tanggal_terdaftar', 'status_kelayakan', 'status_verifikasi', 'catatan', 'diverifikasi_oleh', 'tanggal_verifikasi', 'catatan_verifikasi'];

    protected $casts = ['tanggal_terdaftar' => 'date', 'tanggal_verifikasi' => 'datetime'];

    protected $appends = ['status_alur'];

    public function program()
    {
        return $this->belongsTo(ProgramBantuan::class, 'program_bantuan_id');
    }

    public function penduduk()
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function kartuKeluarga()
    {
        return $this->belongsTo(KartuKeluarga::class, 'kartu_keluarga_id');
    }

    public function penyaluran()
    {
        return $this->hasMany(PenyaluranBantuan::class);
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function getStatusAlurAttribute(): string
    {
        if ($this->status_verifikasi === 'proses_verifikasi') {
            return 'proses_verifikasi';
        }

        if ($this->status_verifikasi === 'terverifikasi') {
            return $this->status_kelayakan === 'layak' ? 'layak' : 'tidak_layak';
        }

        return 'menunggu_verifikasi';
    }
}
