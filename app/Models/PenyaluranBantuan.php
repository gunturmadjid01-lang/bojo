<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenyaluranBantuan extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    protected $fillable = ['penerima_bantuan_id', 'periode', 'tanggal_penyaluran', 'jumlah', 'status', 'diterima_oleh', 'bukti', 'catatan', 'dibuat_oleh'];
    protected $casts = ['tanggal_penyaluran' => 'date', 'jumlah' => 'decimal:2'];
    public function penerima() { return $this->belongsTo(PenerimaBantuan::class, 'penerima_bantuan_id'); }
    public function pembuat() { return $this->belongsTo(User::class, 'dibuat_oleh'); }
}
