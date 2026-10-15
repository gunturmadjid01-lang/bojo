<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    use \App\Models\Concerns\HasWilayahScope;

    use HasFactory;

    protected $fillable = [
        'kode_desa',
        'nama_desa',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'alamat_kantor',
        'telepon',
        'email',
        'logo',
        'aktif',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function penugasanPerangkat(): HasMany
    {
        return $this->hasMany(PenugasanPerangkat::class);
    }
}
