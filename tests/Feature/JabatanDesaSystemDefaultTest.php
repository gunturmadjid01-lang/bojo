<?php

namespace Tests\Feature;

use App\Models\JabatanDesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JabatanDesaSystemDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_system_jabatan_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('super_admin');
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $jabatan = JabatanDesa::create([
            'kode' => 'JBD-0001',
            'nama' => 'Kepala Desa',
            'tingkat' => 'desa',
            'aktif' => true,
            'bawaan_sistem' => true,
        ]);

        $this->put(route('jabatan-desa.update', $jabatan), [
            'nama' => 'Nama Baru',
            'tingkat' => 'desa',
            'aktif' => true,
        ])->assertSessionHasErrors(['bawaan_sistem']);

        $this->assertDatabaseHas('jabatan_desas', [
            'id' => $jabatan->id,
            'nama' => 'Kepala Desa',
        ]);

        $this->delete(route('jabatan-desa.destroy', $jabatan))
            ->assertSessionHasErrors(['bawaan_sistem']);

        $this->assertDatabaseHas('jabatan_desas', [
            'id' => $jabatan->id,
            'nama' => 'Kepala Desa',
        ]);
    }
}
