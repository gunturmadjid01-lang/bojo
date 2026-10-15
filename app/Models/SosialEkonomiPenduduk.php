<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SosialEkonomiPenduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'status_pekerjaan', 'penghasilan_bulanan', 'kategori_penghasilan', 'jaminan_kesehatan', 'tanggal_pendataan', 'didata_oleh'];

    protected function casts(): array
    {
        return [
            'penghasilan_bulanan' => 'decimal:2',
            'tanggal_pendataan' => 'date',
        ];
    }

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function didataOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'didata_oleh');
    }
}
