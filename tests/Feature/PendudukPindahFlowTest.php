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

class PendudukPindahFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_individu_dengan_kk_existing_membuat_penduduk_baru_dan_keanggotaan(): void
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
        $rw = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'nomor' => '02']);
        $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '03']);

        $kk = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '7301000000001001',
            'alamat' => 'Jl. Hasil',
            'status' => 'aktif',
        ]);

        $response = $this->post('/penduduk-pindah', [
            'jenis_pindah' => 'individu',
            'jenis_tujuan_kk' => 'gabung_kk',
            'tanggal_pindah' => '2026-09-15',
            'kartu_keluarga_tujuan_id' => $kk->id,
            'hubungan_keluarga' => 'Anak',
            'nik' => '7301000000004001',
            'nama_lengkap' => 'Nia',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '2008-02-01',
            'jenis_kelamin' => 'Perempuan',
            'agama' => 'Islam',
            'status_perkawinan' => 'Belum Kawin',
            'kewarganegaraan' => 'WNI',
            'alamat' => 'Jl. Tujuan Baru',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
        ]);

        $response->assertRedirect('/penduduk-pindah');

        $penduduk = Penduduk::query()->where('nik', '7301000000004001')->firstOrFail();
        $this->assertDatabaseHas('penduduk_pindahs', ['penduduk_id' => $penduduk->id]);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'penduduk_id' => $penduduk->id,
            'kartu_keluarga_id' => $kk->id,
            'hubungan_keluarga' => 'Anak',
            'aktif' => true,
        ]);
    }

    public function test_individu_dengan_kk_baru_membuat_kk_baru_dan_keanggotaan_kepala_keluarga(): void
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
        $rw = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'nomor' => '02']);
        $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '03']);

        $response = $this->post('/penduduk-pindah', [
            'jenis_pindah' => 'individu',
            'jenis_tujuan_kk' => 'kk_baru',
            'tanggal_pindah' => '2026-09-15',
            'hubungan_keluarga' => 'Kepala Keluarga',
            'nik' => '7301000000005001',
            'nama_lengkap' => 'Anwar',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1990-03-10',
            'jenis_kelamin' => 'Laki-laki',
            'agama' => 'Islam',
            'status_perkawinan' => 'Kawin',
            'kewarganegaraan' => 'WNI',
            'alamat' => 'Jl. Baru',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'kk_baru' => [
                'nomor_kk' => '7301000000002001',
                'desa_id' => $desa->id,
                'dusun_id' => $dusun->id,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
                'alamat' => 'Jl. Baru',
                'kode_pos' => '90511',
            ],
        ]);

        $response->assertRedirect('/penduduk-pindah');

        $penduduk = Penduduk::query()->where('nik', '7301000000005001')->firstOrFail();
        $kk = KartuKeluarga::query()->where('nomor_kk', '7301000000002001')->firstOrFail();
        $this->assertDatabaseHas('penduduk_pindahs', ['penduduk_id' => $penduduk->id]);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'penduduk_id' => $penduduk->id,
            'kartu_keluarga_id' => $kk->id,
            'hubungan_keluarga' => 'Kepala Keluarga',
            'aktif' => true,
        ]);
    }

    public function test_keluarga_dengan_kk_existing_membuat_beberapa_penduduk_baru_dan_keanggotaan(): void
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
        $rw = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'nomor' => '02']);
        $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '03']);

        $kk = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '7301000000003001',
            'alamat' => 'Jl. Lama',
            'status' => 'aktif',
        ]);

        $response = $this->post('/penduduk-pindah', [
            'jenis_pindah' => 'keluarga',
            'jenis_tujuan_kk' => 'gabung_kk',
            'tanggal_pindah' => '2026-09-15',
            'kartu_keluarga_tujuan_id' => $kk->id,
            'anggota' => [
                [
                    'nik' => '7301000000006001',
                    'nama_lengkap' => 'Halim',
                    'tempat_lahir' => 'Bojo',
                    'tanggal_lahir' => '1980-05-10',
                    'jenis_kelamin' => 'Laki-laki',
                    'agama' => 'Islam',
                    'status_perkawinan' => 'Kawin',
                    'kewarganegaraan' => 'WNI',
                    'hubungan_keluarga' => 'Kepala Keluarga',
                    'alamat' => 'Jl. Tujuan Baru',
                    'dusun_id' => $dusun->id,
                    'rw_id' => $rw->id,
                    'rt_id' => $rt->id,
                ],
                [
                    'nik' => '7301000000006002',
                    'nama_lengkap' => 'Mira',
                    'tempat_lahir' => 'Bojo',
                    'tanggal_lahir' => '1982-09-15',
                    'jenis_kelamin' => 'Perempuan',
                    'agama' => 'Islam',
                    'status_perkawinan' => 'Kawin',
                    'kewarganegaraan' => 'WNI',
                    'hubungan_keluarga' => 'Istri',
                    'alamat' => 'Jl. Tujuan Baru',
                    'dusun_id' => $dusun->id,
                    'rw_id' => $rw->id,
                    'rt_id' => $rt->id,
                ],
            ],
        ]);

        $response->assertRedirect('/penduduk-pindah');
        $this->assertDatabaseCount('penduduk_pindahs', 2);
        $this->assertDatabaseHas('anggota_kartu_keluargas', ['kartu_keluarga_id' => $kk->id, 'hubungan_keluarga' => 'Kepala Keluarga']);
        $this->assertDatabaseHas('anggota_kartu_keluargas', ['kartu_keluarga_id' => $kk->id, 'hubungan_keluarga' => 'Istri']);
    }
}
