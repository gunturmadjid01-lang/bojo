<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisabilitasPenduduk extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penduduk_id', 'jenis_disabilitas_id', 'tingkat', 'keterangan'];

    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class);
    }

    public function jenisDisabilitas(): BelongsTo
    {
        return $this->belongsTo(Disabilitas::class, 'jenis_disabilitas_id');
    }
}
