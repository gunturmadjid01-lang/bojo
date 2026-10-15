<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPerkawinan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'pasangan_id', 'status', 'tanggal_mulai', 'tanggal_selesai', 'nomor_dokumen', 'catatan'];
}
