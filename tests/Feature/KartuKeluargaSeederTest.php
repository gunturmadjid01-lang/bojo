<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\RT;
use App\Models\RW;
use Database\Seeders\KartuKeluargaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuKeluargaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_kartu_keluarga_seeder_generates_100_records_across_existing_dusun(): void
    {
        $desa = Desa::create([
            'kode_desa' => '7308012001',
            'nama_desa' => 'Desa Bojo',
            'kecamatan' => 'Mallusetasi',
            'kabupaten' => 'Barru',
            'provinsi' => 'Sulawesi Selatan',
            'aktif' => true,
        ]);

        $dusunA = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Dusun A', 'kode' => '01']);
        $dusunB = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Dusun B', 'kode' => '02']);
        $dusunC = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Dusun C', 'kode' => '03']);

        foreach ([$dusunA, $dusunB, $dusunC] as $dusun) {
            $rw = RW::create(['dusun_id' => $dusun->id, 'nomor' => '01']);
            RT::create(['rw_id' => $rw->id, 'nomor' => '01']);
            RT::create(['rw_id' => $rw->id, 'nomor' => '02']);
        }

        $this->seed(KartuKeluargaSeeder::class);

        $this->assertGreaterThanOrEqual(100, KartuKeluarga::count());
        $this->assertGreaterThan(0, KartuKeluarga::query()->where('dusun_id', $dusunA->id)->count());
        $this->assertGreaterThan(0, KartuKeluarga::query()->where('dusun_id', $dusunB->id)->count());
        $this->assertGreaterThan(0, KartuKeluarga::query()->where('dusun_id', $dusunC->id)->count());
    }
}
