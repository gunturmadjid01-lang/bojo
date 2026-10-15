<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuKeluarga extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['nomor_kk', 'desa_id', 'dusun_id', 'rw_id', 'rt_id', 'alamat', 'kode_pos', 'tanggal_diterbitkan', 'status', 'perlu_kepala_keluarga_baru'];

    protected function casts(): array
    {
        return [
            'tanggal_diterbitkan' => 'date',
            'perlu_kepala_keluarga_baru' => 'boolean',
        ];
    }

    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    public function dusun(): BelongsTo
    {
        return $this->belongsTo(Dusun::class);
    }

    public function rw(): BelongsTo
    {
        return $this->belongsTo(RW::class, 'rw_id');
    }

    public function rt(): BelongsTo
    {
        return $this->belongsTo(RT::class, 'rt_id');
    }

    public function anggotaKeluarga(): HasMany
    {
        return $this->hasMany(AnggotaKartuKeluarga::class);
    }

    public function kepalaKeluarga(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AnggotaKartuKeluarga::class)
            ->where('aktif', true)
            ->where('hubungan_keluarga', 'Kepala Keluarga');
    }
}
