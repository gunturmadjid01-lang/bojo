<?php

namespace Database\Seeders;

use App\Models\Disabilitas;
use Illuminate\Database\Seeder;

class DisabilitasSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['nama' => 'Disabilitas Fisik', 'keterangan' => 'Hambatan pada fungsi gerak tubuh.'],
            ['nama' => 'Disabilitas Sensorik', 'keterangan' => 'Hambatan pada fungsi penglihatan, pendengaran, atau komunikasi.'],
            ['nama' => 'Disabilitas Intelektual', 'keterangan' => 'Hambatan pada fungsi berpikir dan kemampuan adaptif.'],
            ['nama' => 'Disabilitas Mental', 'keterangan' => 'Hambatan pada fungsi emosi, perilaku, atau psikososial.'],
            ['nama' => 'Disabilitas Ganda', 'keterangan' => 'Memiliki lebih dari satu jenis disabilitas.'],
        ];

        foreach ($items as $item) {
            Disabilitas::updateOrCreate(['nama' => $item['nama']], [...$item, 'aktif' => true]);
        }
    }
}
