<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Dusun;
use App\Models\KartuKeluarga;
use App\Models\Penduduk;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KartuKeluargaCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_kk_and_head_of_family_member_in_one_request(): void
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
            'nama' => 'Bontoramba',
        ]);

        $rw = RW::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'nomor' => '02',
        ]);

        $rt = RT::create([
            'rw_id' => $rw->id,
            'nomor' => '03',
        ]);

        $kepala = Penduduk::create([
            'nik' => '7301000000000001',
            'nama_lengkap' => 'Ahmad',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'masih_hidup' => true,
            'alamat' => 'Jl. Raya',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $this->post('/kartu-keluarga', [
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'penduduk_id' => $kepala->id,
            'nomor_kk' => '7301000000000001',
            'alamat' => 'Jl. Raya',
            'kode_pos' => '90245',
            'tanggal_diterbitkan' => '2026-01-01',
            'status' => 'aktif',
        ])->assertRedirect('/kartu-keluarga');

        $kartuKeluarga = KartuKeluarga::query()->where('nomor_kk', '7301000000000001')->firstOrFail();

        $this->assertDatabaseHas('kartu_keluargas', ['id' => $kartuKeluarga->id, 'nomor_kk' => '7301000000000001']);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $kepala->id,
            'hubungan_keluarga' => 'Kepala Keluarga',
            'aktif' => true,
        ]);
    }

    public function test_it_rejects_head_of_family_not_from_selected_dusun_rw_or_rt(): void
    {
        $desa = Desa::create([
            'kode_desa' => '001',
            'nama_desa' => 'Bojo',
            'kecamatan' => 'Kecamatan A',
            'kabupaten' => 'Kabupaten A',
            'provinsi' => 'Provinsi A',
            'aktif' => true,
        ]);

        $dusun = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Bontoramba']);
        $dusunLain = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Lain']);

        $rw = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'nomor' => '02']);
        $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '03']);

        $rwLain = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusunLain->id, 'nomor' => '05']);
        $rtLain = RT::create(['rw_id' => $rwLain->id, 'nomor' => '01']);

        $penduduk = Penduduk::create([
            'nik' => '7301000000000002',
            'nama_lengkap' => 'Budi',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1990-02-02',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'masih_hidup' => true,
            'alamat' => 'Jl. Lain',
            'dusun_id' => $dusunLain->id,
            'rw_id' => $rwLain->id,
            'rt_id' => $rtLain->id,
        ]);

        $this->from('/kartu-keluarga/create')
            ->post('/kartu-keluarga', [
                'desa_id' => $desa->id,
                'dusun_id' => $dusun->id,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
                'penduduk_id' => $penduduk->id,
                'nomor_kk' => '7301000000000002',
                'alamat' => 'Jl. Lama',
                'kode_pos' => '90245',
                'tanggal_diterbitkan' => '2026-01-01',
                'status' => 'aktif',
            ])
            ->assertSessionHasErrors('penduduk_id');

        $this->assertDatabaseMissing('kartu_keluargas', ['nomor_kk' => '7301000000000002']);
    }
}
