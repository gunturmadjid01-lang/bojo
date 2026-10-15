<?php

namespace Database\Seeders;

use App\Models\Desa;
use Illuminate\Database\Seeder;

class DesaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(\App\Services\WilayahAccess::class)->forSystem(fn () => $this->seedData());
    }

    private function seedData(): void
    {
        Desa::updateOrCreate(
            ['kode_desa' => '7308012001'],
            [
                'nama_desa' => 'Desa Bojo',
                'kecamatan' => 'Mallusetasi',
                'kabupaten' => 'Barru',
                'provinsi' => 'Sulawesi Selatan',
                'kode_pos' => '90753',
                'alamat_kantor' => 'Jl. Poros Bojo, Desa Bojo',
                'telepon' => '(0427) 123456',
                'email' => 'desabojo@example.com',
                'logo' => 'logo-desa-bojo.svg',
                'aktif' => true,
            ],
        );
    }
}
