<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Database\Seeders\PendudukSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendudukSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_penduduk_seeder_generates_900_records_across_existing_dusun(): void
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

        $this->seed(PendudukSeeder::class);

        $this->assertSame(900, Penduduk::count());
        $this->assertGreaterThan(0, Penduduk::query()->where('dusun_id', $dusunA->id)->count());
        $this->assertGreaterThan(0, Penduduk::query()->where('dusun_id', $dusunB->id)->count());
        $this->assertGreaterThan(0, Penduduk::query()->where('dusun_id', $dusunC->id)->count());
    }
}
