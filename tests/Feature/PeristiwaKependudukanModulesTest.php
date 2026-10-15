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

class PeristiwaKependudukanModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_kelahiran_creates_new_baby_and_links_to_selected_kk(): void
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

        $kartuKeluarga = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '7301000000000001',
            'alamat' => 'Jl. Poros Desa',
            'status' => 'aktif',
        ]);

        $ayah = Penduduk::create([
            'nik' => '7301000000000001',
            'nama_lengkap' => 'Ahmad',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $ibu = Penduduk::create([
            'nik' => '7301000000000002',
            'nama_lengkap' => 'Siti',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1987-01-01',
            'jenis_kelamin' => 'Perempuan',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $this->post('/kelahiran', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'nama_lengkap' => 'Muhammad Fadil',
            'nik' => null,
            'jenis_kelamin' => 'Laki-laki',
            'tempat_lahir' => 'Makassar',
            'tanggal_lahir' => '2026-09-14',
            'waktu_lahir' => '08:30:00',
            'nomor_akta_kelahiran' => 'AK-2026-001',
            'tanggal_akta_kelahiran' => '2026-09-14',
            'anak_ke' => 1,
            'berat_lahir' => 3.2,
            'panjang_lahir' => 50.5,
            'tanggal_dilaporkan' => '2026-09-14',
            'catatan' => 'Bayi sehat',
        ])->assertRedirect('/kelahiran');

        $pendudukBaru = Penduduk::query()->where('nama_lengkap', 'Muhammad Fadil')->firstOrFail();

        $this->assertSame('Jl. Poros Desa', $pendudukBaru->alamat);
        $this->assertSame($dusun->id, $pendudukBaru->dusun_id);
        $this->assertSame($rw->id, $pendudukBaru->rw_id);
        $this->assertSame($rt->id, $pendudukBaru->rt_id);
        $this->assertSame('Ahmad', $pendudukBaru->nama_ayah);
        $this->assertSame('Siti', $pendudukBaru->nama_ibu);
        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $pendudukBaru->id,
            'hubungan_keluarga' => 'Anak',
            'aktif' => true,
        ]);
        $this->assertDatabaseHas('kelahirans', [
            'penduduk_id' => $pendudukBaru->id,
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
        ]);
        $this->assertDatabaseHas('peristiwa_kependudukans', [
            'penduduk_id' => $pendudukBaru->id,
            'jenis' => 'kelahiran',
        ]);
    }

    public function test_kematian_records_death_and_updates_family_state(): void
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

        $kartuKeluarga = KartuKeluarga::create([
            'desa_id' => $desa->id,
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'nomor_kk' => '7301000000000001',
            'alamat' => 'Jl. Poros Desa',
            'status' => 'aktif',
        ]);

        $penduduk = Penduduk::create([
            'nik' => '7301000000000001',
            'nama_lengkap' => 'Ahmad',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1985-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        \App\Models\AnggotaKartuKeluarga::create([
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'penduduk_id' => $penduduk->id,
            'hubungan_keluarga' => 'Kepala Keluarga',
            'tanggal_masuk' => '2020-01-01',
            'aktif' => true,
        ]);

        $this->post('/kematian', [
            'penduduk_id' => $penduduk->id,
            'tanggal_meninggal' => '2026-09-14',
            'waktu_meninggal' => '08:30:00',
            'tempat_meninggal' => 'Rumah',
            'penyebab_meninggal' => 'Sakit',
            'nomor_surat_kematian' => 'SK-2026-001',
            'tanggal_dilaporkan' => '2026-09-14',
            'catatan' => 'Kematian karena sakit lama',
        ])->assertRedirect('/kematian');

        $this->assertDatabaseHas('kematians', [
            'penduduk_id' => $penduduk->id,
            'nomor_surat_kematian' => 'SK-2026-001',
        ]);

        $this->assertDatabaseHas('penduduks', [
            'id' => $penduduk->id,
            'status_penduduk' => 'meninggal',
            'masih_hidup' => false,
        ]);

        $this->assertDatabaseHas('anggota_kartu_keluargas', [
            'penduduk_id' => $penduduk->id,
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'aktif' => false,
            'alasan_keluar' => 'meninggal',
        ]);

        $this->assertDatabaseHas('peristiwa_kependudukans', [
            'penduduk_id' => $penduduk->id,
            'jenis' => 'kematian',
            'nomor_referensi' => 'SK-2026-001',
        ]);
    }

    public function test_population_event_modules_are_available_and_store_records(): void
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

        $ayah = Penduduk::create([
            'nik' => '3201010101010002',
            'nama_lengkap' => 'Ayah Budi',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1965-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $ibu = Penduduk::create([
            'nik' => '3201010101010003',
            'nama_lengkap' => 'Ibu Budi',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1967-01-01',
            'jenis_kelamin' => 'Perempuan',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $this->get('/kelahiran/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('kelahiran/form'));

        $this->post('/kelahiran', [
            'penduduk_id' => $penduduk->id,
            'kartu_keluarga_id' => $kartuKeluarga->id,
            'ayah_id' => $ayah->id,
            'ibu_id' => $ibu->id,
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '2026-01-10',
            'anak_ke' => 1,
            'berat_lahir' => 3.2,
            'panjang_lahir' => 50.0,
            'nomor_akta_kelahiran' => 'AK-001',
        ])->assertRedirect('/kelahiran');

        $this->assertDatabaseHas('kelahirans', ['penduduk_id' => $penduduk->id, 'anak_ke' => 1]);

        $this->get('/kematian/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('kematian/form'));

        $this->post('/kematian', [
            'penduduk_id' => $penduduk->id,
            'tanggal_meninggal' => '2026-02-15',
            'tempat_meninggal' => 'Bojo',
            'penyebab_meninggal' => 'Sakit',
            'nomor_surat_kematian' => 'SK-001',
        ])->assertRedirect('/kematian');

        $this->assertDatabaseHas('kematians', ['penduduk_id' => $penduduk->id, 'nomor_surat_kematian' => 'SK-001']);

        $this->get('/penduduk-datang/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('penduduk-datang/form'));

        $pendudukDatang = Penduduk::create([
            'nik' => '3201010101010004',
            'nama_lengkap' => 'Citra',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1995-01-01',
            'jenis_kelamin' => 'Perempuan',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $this->post('/penduduk-datang', [
            'jenis_kedatangan' => 'individu',
            'tanggal_datang' => '2026-03-01',
            'jenis_tujuan_kk' => 'gabung_kk',
            'penduduk_id' => $pendudukDatang->id,
            'hubungan_keluarga' => 'Anak',
            'desa_sebelumnya' => 'Desa Lama',
            'kecamatan_sebelumnya' => 'Kec Lama',
            'kabupaten_sebelumnya' => 'Kab Lama',
            'provinsi_sebelumnya' => 'Prov Lama',
            'kartu_keluarga_tujuan_id' => $kartuKeluarga->id,
            'nomor_surat_pindah' => 'SP-001',
            'alasan' => 'Pindah domisili',
        ])->assertRedirect('/penduduk-datang/1');

        $this->assertDatabaseHas('kedatangan_penduduks', ['kartu_keluarga_tujuan_id' => $kartuKeluarga->id, 'desa_sebelumnya' => 'Desa Lama']);
        $this->assertDatabaseHas('anggota_kedatangan', ['penduduk_id' => $pendudukDatang->id, 'hubungan_keluarga' => 'Anak']);

        $this->get('/penduduk-pindah/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('penduduk-pindah/form'));

        $pendudukPindah = Penduduk::create([
            'nik' => '3201010101010005',
            'nama_lengkap' => 'Dewi',
            'tempat_lahir' => 'Bojo',
            'tanggal_lahir' => '1992-01-01',
            'jenis_kelamin' => 'Perempuan',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'dusun_id' => $dusun->id,
            'rw_id' => $rw->id,
            'rt_id' => $rt->id,
            'masih_hidup' => true,
        ]);

        $this->post('/penduduk-pindah', [
            'penduduk_id' => $pendudukPindah->id,
            'tanggal_pindah' => '2026-04-01',
            'alamat_tujuan' => 'Jl. Baru',
            'desa_tujuan' => 'Desa Baru',
            'kecamatan_tujuan' => 'Kec Baru',
            'kabupaten_tujuan' => 'Kab Baru',
            'provinsi_tujuan' => 'Prov Baru',
            'nomor_surat_pindah' => 'SP-002',
            'alasan' => 'Pekerjaan',
        ])->assertRedirect('/penduduk-pindah');

        $this->assertDatabaseHas('penduduk_pindahs', ['penduduk_id' => $pendudukPindah->id, 'desa_tujuan' => 'Desa Baru']);
    }
}
