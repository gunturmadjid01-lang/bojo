<?php

namespace Tests\Feature\Api;

use App\Models\JabatanDesa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PerangkatDesaTest extends TestCase
{
    use RefreshDatabase;

    public function test_jabatan_api_requires_matching_action_permission(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('view jabatan-desa');
        $user->givePermissionTo('view jabatan-desa');
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $this->getJson('/api/v1/jabatan-desa')->assertOk();
        $this->postJson('/api/v1/jabatan-desa', ['nama' => 'Kaur Baru', 'tingkat' => 'desa', 'aktif' => true])->assertForbidden();
    }

    public function test_jabatan_api_creates_generated_code_and_protects_system_default(): void
    {
        $user = User::factory()->create();
        foreach (['view', 'create', 'edit', 'delete'] as $action) Permission::findOrCreate("{$action} jabatan-desa");
        $user->givePermissionTo(['view jabatan-desa', 'create jabatan-desa', 'edit jabatan-desa', 'delete jabatan-desa']);
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $created = $this->postJson('/api/v1/jabatan-desa', ['nama' => 'Kaur Baru', 'tingkat' => 'desa', 'aktif' => true])
            ->assertCreated()->assertJsonPath('data.kode', 'JBD-0001');
        $this->assertDatabaseHas('jabatan_desas', ['id' => $created->json('data.id'), 'nama' => 'Kaur Baru']);

        $system = JabatanDesa::create(['kode' => 'JBD-0002', 'nama' => 'Sekretaris Desa', 'tingkat' => 'desa', 'aktif' => true, 'bawaan_sistem' => true]);
        $this->putJson("/api/v1/jabatan-desa/{$system->id}", ['nama' => 'Diubah', 'tingkat' => 'desa', 'aktif' => true])->assertUnprocessable();
        $this->deleteJson("/api/v1/jabatan-desa/{$system->id}")->assertUnprocessable();
        $this->assertDatabaseHas('jabatan_desas', ['id' => $system->id, 'nama' => 'Sekretaris Desa']);
    }

    public function test_history_permission_does_not_open_active_assignment_list(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('lihat-riwayat-penugasan');
        $user->givePermissionTo('lihat-riwayat-penugasan');
        Sanctum::actingAs($user, ['api:read', 'api:write']);

        $this->getJson('/api/v1/penugasan-perangkat/history')->assertOk();
        $this->getJson('/api/v1/penugasan-perangkat')->assertForbidden();
    }
}
