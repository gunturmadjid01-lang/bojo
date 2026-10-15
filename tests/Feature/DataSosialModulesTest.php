<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Disabilitas;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DataSosialModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_sosial_modules_are_available_and_store_records(): void
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

        $jenisDisabilitas = Disabilitas::create([
            'nama' => 'Tunanetra',
            'keterangan' => 'Kesulitan melihat',
            'aktif' => true,
        ]);

        $this->get('/sosial-ekonomi-keluarga/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('sosial-ekonomi-keluarga/form'));

        $this->post('/sosial-ekonomi-keluarga', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'status_kepemilikan_rumah' => 'Milik Sendiri',
            'kondisi_rumah' => 'Baik',
            'jenis_lantai' => 'Keramik',
            'sumber_air' => 'PDAM',
            'kategori_ekonomi' => 'Menengah',
        ])->assertRedirect('/sosial-ekonomi-keluarga');

        $this->assertDatabaseHas('sosial_ekonomi_keluargas', ['kartu_keluarga_id' => $kartuKeluarga->id, 'kategori_ekonomi' => 'Menengah']);

        $this->get('/sosial-ekonomi-penduduk/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('sosial-ekonomi-penduduk/form'));

        $this->post('/sosial-ekonomi-penduduk', [
            'penduduk_id' => $penduduk->id,
            'status_pekerjaan' => 'Bekerja',
            'penghasilan_bulanan' => 2500000,
            'kategori_penghasilan' => 'Menengah',
            'jaminan_kesehatan' => 'BPJS',
        ])->assertRedirect('/sosial-ekonomi-penduduk');

        $this->assertDatabaseHas('sosial_ekonomi_penduduks', ['penduduk_id' => $penduduk->id, 'jaminan_kesehatan' => 'BPJS']);

        $this->get('/disabilitas-penduduk/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('disabilitas-penduduk/form'));

        $this->post('/disabilitas-penduduk', [
            'penduduk_id' => $penduduk->id,
            'jenis_disabilitas_id' => $jenisDisabilitas->id,
            'tingkat' => 'Ringan',
            'keterangan' => 'Sulit melihat saat malam hari',
        ])->assertRedirect('/disabilitas-penduduk');

        $this->assertDatabaseHas('disabilitas_penduduks', ['penduduk_id' => $penduduk->id, 'tingkat' => 'Ringan']);
    }
}
