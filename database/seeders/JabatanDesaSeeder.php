<?php

namespace Database\Seeders;

use App\Models\JabatanDesa;
use Illuminate\Database\Seeder;

class JabatanDesaSeeder extends Seeder
{
    public function run(): void
    {
        $defaultJabatans = [
            [
                'kode' => 'JBD-0001',
                'nama' => 'Kepala Desa',
                'tingkat' => 'desa',
                'aktif' => true,
                'bawaan_sistem' => true,
            ],
            [
                'kode' => 'JBD-0002',
                'nama' => 'Sekretaris Desa',
                'tingkat' => 'desa',
                'aktif' => true,
                'bawaan_sistem' => true,
            ],
            [
                'kode' => 'JBD-0003',
                'nama' => 'Kepala Dusun',
                'tingkat' => 'dusun',
                'aktif' => true,
                'bawaan_sistem' => true,
            ],
        ];

        foreach ($defaultJabatans as $jabatan) {
            JabatanDesa::updateOrCreate(
                ['kode' => $jabatan['kode']],
                $jabatan
            );
        }
    }
}
