<?php

namespace Tests\Feature\Api;

use App\Models\Penduduk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PendudukUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_display_name_can_update_penduduk_using_android_payload(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole(Role::findOrCreate('Super Admin', 'web'));
        Sanctum::actingAs($actor, ['api:read', 'api:write']);

        $penduduk = Penduduk::create([
            'nik' => '3374000000000001',
            'nama_lengkap' => 'Nama Lama',
            'jenis_kelamin' => 'Laki-laki',
            'kewarganegaraan' => 'WNI',
            'status_penduduk' => 'aktif',
            'masih_hidup' => true,
        ]);

        $response = $this->putJson("/api/v1/penduduk/{$penduduk->id}", [
            'nik' => $penduduk->nik,
            'nama_lengkap' => 'Nama Diperbarui',
            'tempat_lahir' => '',
            'tanggal_lahir' => null,
            'jenis_kelamin' => 'Laki-laki',
            'agama' => null,
            'golongan_darah' => null,
            'status_perkawinan' => null,
            'pendidikan_id' => null,
            'pekerjaan_id' => null,
            'kewarganegaraan' => 'WNI',
            'nama_ayah' => '',
            'nama_ibu' => '',
            'telepon' => '',
            'alamat' => '',
            'dusun_id' => null,
            'rw_id' => null,
            'rt_id' => null,
            'status_penduduk' => 'aktif',
            'masih_hidup' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.nama_lengkap', 'Nama Diperbarui');
        $this->assertDatabaseHas('penduduks', ['id' => $penduduk->id, 'nama_lengkap' => 'Nama Diperbarui']);
    }
}
