<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramBantuan extends Model
{
    protected $fillable = ['kode', 'nama', 'sumber', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'anggaran', 'status', 'keterangan'];
    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date', 'anggaran' => 'decimal:2'];
    public function penerima() { return $this->hasMany(PenerimaBantuan::class); }
}
