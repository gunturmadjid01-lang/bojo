<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    use HasFactory;

    protected $fillable = ['nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'golongan_darah', 'status_perkawinan', 'pendidikan_id', 'pekerjaan_id', 'kewarganegaraan', 'nama_ayah', 'nama_ibu', 'telepon', 'email', 'alamat', 'dusun_id', 'rw_id', 'rt_id', 'status_penduduk', 'masih_hidup', 'foto'];

    protected function casts(): array
    {
        return [
            'masih_hidup' => 'boolean',
            'tanggal_lahir' => 'date',
        ];
    }

    public function pendidikan(): BelongsTo
    {
        return $this->belongsTo(Pendidikan::class);
    }

    public function pekerjaan(): BelongsTo
    {
        return $this->belongsTo(Pekerjaan::class);
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

    public function anggotaKartuKeluarga(): HasMany
    {
        return $this->hasMany(AnggotaKartuKeluarga::class);
    }
}
