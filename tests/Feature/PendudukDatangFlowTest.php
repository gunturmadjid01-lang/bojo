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

class PendudukDatangFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_individu_baru_dapat_masuk_ke_kk_tujuan_dan_membuat_peristiwa(): void
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

        $kartuKeluarga = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '7301000000000001',
            'alamat' => 'Jl. Poros Desa',
            'status' => 'aktif',
        ]);

        $response = $this->post('/penduduk-datang', [
            'jenis_kedatangan' => 'individu',
            'tanggal_datang' => '2026-09-14',
            'jenis_tujuan_kk' => 'gabung_kk',
            'kartu_keluarga_tujuan_id' => $kartuKeluarga->id,
            'hubungan_keluarga' => 'Anak',
            'penduduk' => [
                'nik' => '7301000000002001',
                'nama_lengkap' => 'Budi',
                'tempat_lahir' => 'Bojo',
                'tanggal_lahir' => '2005-06-01',
                'jenis_kelamin' => 'Laki-laki',
                'agama' => 'Islam',
                'status_perkawinan' => 'Belum Kawin',
                'kewarganegaraan' => 'WNI',
                'nama_ayah' => 'Ahmad',
                'nama_ibu' => 'Siti',
                'telepon' => '081234567890',
            ],
        ]);

        $response->assertRedirect();

        $penduduk = Penduduk::query()->where('nik', '7301000000002001')->firstOrFail();
        $this->assertDatabaseHas('kedatangan_penduduks', [
            'jenis_kedatangan' => 'individu',
            'kartu_keluarga_tujuan_id' => $kartuKeluarga->id,
        ]);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'penduduk_id' => $penduduk->id,
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'hubungan_keluarga' => 'Anak',
            'aktif' => true,
        ]);
        $this->assertDatabaseHas('anggota_kedatangan', [
            'penduduk_id' => $penduduk->id,
            'hubungan_keluarga' => 'Anak',
        ]);
        $this->assertDatabaseHas('peristiwa_kependudukans', [
            'penduduk_id' => $penduduk->id,
            'jenis' => 'datang',
        ]);
    }

    public function test_keluarga_baru_menghasilkan_satu_header_dan_banyak_anggota(): void
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

        $response = $this->post('/penduduk-datang', [
            'jenis_kedatangan' => 'keluarga',
            'tanggal_datang' => '2026-09-14',
            'jenis_tujuan_kk' => 'kk_baru',
            'catatan' => 'Pindah bersama keluarga',
            'kk_baru' => [
                'nomor_kk' => '7301000000000002',
                'alamat' => 'Jl. Poros Desa',
                'dusun_id' => $dusun->id,
                'rw_id' => $rw->id,
                'rt_id' => $rt->id,
                'kode_pos' => '90511',
            ],
            'anggota' => [
                [
                    'nik' => '7301000000003001',
                    'nama_lengkap' => 'Ahmad',
                    'tempat_lahir' => 'Bojo',
                    'tanggal_lahir' => '1985-01-01',
                    'jenis_kelamin' => 'Laki-laki',
                    'agama' => 'Islam',
                    'status_perkawinan' => 'Kawin',
                    'kewarganegaraan' => 'WNI',
                    'hubungan_keluarga' => 'Kepala Keluarga',
                ],
                [
                    'nik' => '7301000000003002',
                    'nama_lengkap' => 'Siti',
                    'tempat_lahir' => 'Bojo',
                    'tanggal_lahir' => '1987-01-01',
                    'jenis_kelamin' => 'Perempuan',
                    'agama' => 'Islam',
                    'status_perkawinan' => 'Kawin',
                    'kewarganegaraan' => 'WNI',
                    'hubungan_keluarga' => 'Istri',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('kedatangan_penduduks', 1);
        $this->assertDatabaseCount('anggota_kedatangan', 2);
        $this->assertDatabaseHas('kartu_keluargas', ['nomor_kk' => '7301000000000002']);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'hubungan_keluarga' => 'Kepala Keluarga',
            'aktif' => true,
        ]);
    }
}
