<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AnggotaKartuKeluargaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_anggota_kartu_keluarga_module_is_available_and_can_store_records(): void
    {
        $desa = Desa::create([
            'kode_desa' => '001',
            'nama_desa' => 'Bojo',
            'kecamatan' => 'Kecamatan A',
            'kabupaten' => 'Kabupaten A',
            'provinsi' => 'Provinsi A',
            'aktif' => true,
        ]);

        $dusun = Dusun::create([
            'desa_id' => $desa->id,
            'nama' => 'Dusun A',
        ]);

        $rw = RW::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'nomor' => '01',
        ]);

        $rt = RT::create([
            'rw_id' => $rw->id,
            'nomor' => '01',
        ]);

        $kartuKeluarga = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '3201010001',
            'alamat' => 'Jl. Desa',
            'status' => 'aktif',
        ]);

        $penduduk = Penduduk::create([
            'nik' => '3201010101010001',
            'nama_lengkap' => 'Budi',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1990-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $pendudukKedua = Penduduk::create([
            'nik' => '3201010101010002',
            'nama_lengkap' => 'Siti',
            'jenis_kelamin' => 'Perempuan',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $this->get('/anggota-kartu-keluarga/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('anggota-kartu-keluarga/create')
                ->has('kartuKeluargas', 1)
                ->has('penduduk', 2));

        $this->post('/anggota-kartu-keluarga', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'anggotas' => [
                [
                    'penduduk_id' => $penduduk->id,
                    'hubungan_keluarga' => 'Kepala Keluarga',
                    'tanggal_masuk' => '2024-01-05',
                ],
                [
                    'penduduk_id' => $pendudukKedua->id,
                    'hubungan_keluarga' => 'Istri',
                    'tanggal_masuk' => '2024-01-05',
                ],
            ],
        ])->assertRedirect("/kartu-keluarga/{$kartuKeluarga->id}");

        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $penduduk->id,
            'hubungan_keluarga' => 'Kepala Keluarga',
            'aktif' => true,
        ]);

        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $pendudukKedua->id,
            'hubungan_keluarga' => 'Istri',
            'aktif' => true,
        ]);

        $this->get("/kartu-keluarga/{$kartuKeluarga->id}")
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('kartu-keluarga/show')
                ->has('kartuKeluarga.anggota_keluarga', 2));
    }
}
