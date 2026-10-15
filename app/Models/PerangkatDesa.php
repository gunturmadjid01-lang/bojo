<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerangkatDesa extends Model
{
    use SoftDeletes;

    protected $fillable = ['penduduk_id', 'user_id', 'nik', 'nama_lengkap', 'telepon', 'alamat', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function penduduk(): BelongsTo { return $this->belongsTo(Penduduk::class); }
    public function penugasan(): HasMany { return $this->hasMany(PenugasanPerangkat::class); }
    public function penugasanAktif(): HasMany { return $this->penugasan()->withoutGlobalScope('wilayah')->aktif(); }
}
