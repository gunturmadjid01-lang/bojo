<?php

namespace Database\Seeders;

use App\Models\Pekerjaan;
use Illuminate\Database\Seeder;

class PekerjaanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pekerjaans = [
            ['kode' => 'BLM_BEKERJA', 'nama' => 'Belum/Tidak Bekerja'],
            ['kode' => 'MENGURUS_RUMAH_TANGGA', 'nama' => 'Mengurus Rumah Tangga'],
            ['kode' => 'PELAJAR', 'nama' => 'Pelajar/Mahasiswa'],
            ['kode' => 'PNS', 'nama' => 'Pegawai Negeri Sipil'],
            ['kode' => 'TNI', 'nama' => 'Tentara Nasional Indonesia'],
            ['kode' => 'POLRI', 'nama' => 'Kepolisian Republik Indonesia'],
            ['kode' => 'PEGAWAI_SWASTA', 'nama' => 'Pegawai Swasta'],
            ['kode' => 'WIRASWASTA', 'nama' => 'Wiraswasta'],
            ['kode' => 'PEDAGANG', 'nama' => 'Pedagang'],
            ['kode' => 'PETANI', 'nama' => 'Petani'],
            ['kode' => 'NELAYAN', 'nama' => 'Nelayan'],
            ['kode' => 'BURUH', 'nama' => 'Buruh'],
            ['kode' => 'GURU', 'nama' => 'Guru/Dosen'],
            ['kode' => 'DOKTER', 'nama' => 'Dokter/Tenaga Kesehatan'],
            ['kode' => 'PENSIUNAN', 'nama' => 'Pensiunan'],
            ['kode' => 'LAINNYA', 'nama' => 'Pekerjaan Lainnya'],
        ];

        foreach ($pekerjaans as $pekerjaan) {
            Pekerjaan::updateOrCreate(['kode' => $pekerjaan['kode']], $pekerjaan);
        }
    }
}
