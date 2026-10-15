<?php

namespace Tests\Feature;

use App\Models\{AnggotaKartuKeluarga, Desa, Dusun, KartuKeluarga, Penduduk, RT, RW, User};
use App\Services\WilayahAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StructuredPendudukPindahTest extends TestCase
{
    use RefreshDatabase;

    public function test_individu_pindah_kk_dalam_desa_menutup_keanggotaan_lama(): void
    {
        [$user, $source, $target, $person] = $this->fixture();

        $this->actingAs($user)->post('/penduduk-pindah', [
            'jenis_pindah' => 'individu',
            'tujuan_pindah' => 'dalam_desa',
            'penduduk_id' => $person->id,
            'kartu_keluarga_tujuan_id' => $target->id,
            'jenis_tujuan_kk' => 'gabung_kk',
            'hubungan_keluarga' => 'Anak',
            'tanggal_pindah' => '2026-09-15',
        ])->assertRedirect('/penduduk-pindah');

        $this->assertDatabaseHas('anggota_kartu_keluargas', ['penduduk_id' => $person->id, 'kartu_keluarga_id' => $source->id, 'aktif' => false, 'alasan_keluar' => 'pindah_kk']);
        $this->assertDatabaseHas('anggota_kartu_keluargas', ['penduduk_id' => $person->id, 'kartu_keluarga_id' => $target->id, 'aktif' => true, 'hubungan_keluarga' => 'Anak']);
        $this->assertDatabaseHas('perpindahan_penduduks', ['jenis_pindah' => 'individu', 'tujuan_pindah' => 'dalam_desa']);
    $this->assertDatabaseHas('anggota_perpindahan', ['penduduk_id' => $person->id]);
        $this->assertDatabaseHas('peristiwa_kependudukans', ['penduduk_id' => $person->id, 'jenis' => 'pindah']);
        $this->assertSame('aktif', $person->fresh()->status_penduduk);
    }

    public function test_individu_keluar_desa_menjadi_pindah_tanpa_menghapus_penduduk(): void
    {
        [$user, $source, $target, $person] = $this->fixture();

        $this->actingAs($user)->post('/penduduk-pindah', [
            'jenis_pindah' => 'individu',
            'tujuan_pindah' => 'keluar_desa',
            'penduduk_id' => $person->id,
            'tanggal_pindah' => '2026-09-15',
            'alamat_tujuan' => 'Jl. Tujuan',
            'desa_tujuan' => 'Desa Tujuan',
            'kecamatan_tujuan' => 'Kecamatan Tujuan',
            'kabupaten_tujuan' => 'Kabupaten Tujuan',
            'provinsi_tujuan' => 'Provinsi Tujuan',
        ])->assertRedirect('/penduduk-pindah');

        $this->assertDatabaseHas('penduduks', ['id' => $person->id, 'status_penduduk' => 'pindah', 'masih_hidup' => true]);
        $this->assertDatabaseHas('anggota_kartu_keluargas', ['penduduk_id' => $person->id, 'aktif' => false, 'alasan_keluar' => 'pindah_keluar_desa']);
        $this->assertDatabaseHas('perpindahan_penduduks', ['tujuan_pindah' => 'keluar_desa']);
    }

    private function fixture(): array
    {
        return app(WilayahAccess::class)->forSystem(function (): array {
            $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
            $user = User::factory()->create();
            $user->assignRole($role);
            $desa = Desa::create(['nama_desa' => 'Desa Uji', 'aktif' => true]);
            $dusun = Dusun::create(['desa_id' => $desa->id, 'nama' => 'Dusun Uji', 'aktif' => true]);
            $rw = RW::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'nomor' => '01', 'aktif' => true]);
            $rt = RT::create(['rw_id' => $rw->id, 'nomor' => '01', 'aktif' => true]);
            $source = KartuKeluarga::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'rw_id' => $rw->id, 'rt_id' => $rt->id, 'nomor_kk' => '7301000000009001', 'alamat' => 'Alamat Lama', 'status' => 'aktif']);
            $target = KartuKeluarga::create(['desa_id' => $desa->id, 'dusun_id' => $dusun->id, 'rw_id' => $rw->id, 'rt_id' => $rt->id, 'nomor_kk' => '7301000000009002', 'alamat' => 'Alamat Tujuan', 'status' => 'aktif']);
            $person = Penduduk::create(['nik' => '7301000000009001', 'nama_lengkap' => 'Penduduk Uji', 'jenis_kelamin' => 'Laki-laki', 'kewarganegaraan' => 'WNI', 'dusun_id' => $dusun->id, 'rw_id' => $rw->id, 'rt_id' => $rt->id, 'status_penduduk' => 'aktif', 'masih_hidup' => true]);
            AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $source->id, 'penduduk_id' => $person->id, 'hubungan_keluarga' => 'Kepala Keluarga', 'tanggal_masuk' => '2026-01-01', 'aktif' => true]);
            return [$user, $source, $target, $person];
        });
    }
}
