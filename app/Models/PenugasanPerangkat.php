<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class PenugasanPerangkat extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['perangkat_desa_id', 'jabatan_desa_id', 'desa_id', 'dusun_id', 'tanggal_mulai', 'tanggal_selesai', 'nomor_sk_pengangkatan', 'tanggal_sk_pengangkatan', 'nomor_sk_pemberhentian', 'tanggal_sk_pemberhentian', 'status', 'catatan', 'dibuat_oleh'];

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'tanggal_sk_pengangkatan' => 'date', 'tanggal_sk_pemberhentian' => 'date'];

    public function perangkatDesa(): BelongsTo
    {
        return $this->belongsTo(PerangkatDesa::class);
    }
    public function jabatanDesa(): BelongsTo
    {
        return $this->belongsTo(JabatanDesa::class);
    }
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }
    public function dusun(): BelongsTo
    {
        return $this->belongsTo(Dusun::class);
    }
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function scopeAktif($query)
    {
        return $query->where('status', 'aktif')->whereDate('tanggal_mulai', '<=', Carbon::today())->where(fn($q) => $q->whereNull('tanggal_selesai')->orWhereDate('tanggal_selesai', '>=', Carbon::today()));
    }
}
