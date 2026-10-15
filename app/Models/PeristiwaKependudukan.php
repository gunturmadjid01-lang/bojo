<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeristiwaKependudukan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'jenis', 'tanggal_peristiwa', 'nomor_referensi', 'keterangan', 'dibuat_oleh'];
}
