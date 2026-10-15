<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenPenduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'kartu_keluarga_id', 'jenis_dokumen', 'nomor_dokumen', 'tanggal_dokumen', 'file', 'keterangan', 'diunggah_oleh'];
}
