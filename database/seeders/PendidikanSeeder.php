<?php

namespace Database\Seeders;

use App\Models\Pendidikan;
use Illuminate\Database\Seeder;

class PendidikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pendidikans = [
            ['kode' => 'TT', 'nama' => 'Tidak/Belum Sekolah'],
            ['kode' => 'TK', 'nama' => 'Taman Kanak-Kanak'],
            ['kode' => 'SD', 'nama' => 'Sekolah Dasar'],
            ['kode' => 'SMP', 'nama' => 'Sekolah Menengah Pertama'],
            ['kode' => 'SMA', 'nama' => 'Sekolah Menengah Atas'],
            ['kode' => 'SMK', 'nama' => 'Sekolah Menengah Kejuruan'],
            ['kode' => 'D1', 'nama' => 'Diploma I'],
            ['kode' => 'D2', 'nama' => 'Diploma II'],
            ['kode' => 'D3', 'nama' => 'Diploma III'],
            ['kode' => 'D4', 'nama' => 'Diploma IV'],
            ['kode' => 'S1', 'nama' => 'Strata I'],
            ['kode' => 'S2', 'nama' => 'Strata II'],
            ['kode' => 'S3', 'nama' => 'Strata III'],
        ];

        foreach ($pendidikans as $pendidikan) {
            Pendidikan::updateOrCreate(['kode' => $pendidikan['kode']], $pendidikan);
        }
    }
}
