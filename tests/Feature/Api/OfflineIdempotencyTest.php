<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OfflineIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_retried_offline_request_returns_original_response_without_duplicate_write(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create jabatan-desa');
        $user->givePermissionTo('create jabatan-desa');
        Sanctum::actingAs($user, ['api:write']);

        $headers = ['X-Offline-Request-Id' => 'offline-test-001'];
        $payload = ['nama' => 'Kaur Offline', 'tingkat' => 'desa', 'aktif' => true];

        $first = $this->withHeaders($headers)->postJson('/api/v1/jabatan-desa', $payload)->assertCreated();
        $second = $this->withHeaders($headers)->postJson('/api/v1/jabatan-desa', $payload)->assertCreated();

        $second->assertExactJson($first->json());
        $this->assertDatabaseCount('jabatan_desas', 1);
        $this->assertDatabaseCount('offline_requests', 1);
    }

    public function test_same_offline_request_id_cannot_be_reused_for_different_payload(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('create jabatan-desa');
        $user->givePermissionTo('create jabatan-desa');
        Sanctum::actingAs($user, ['api:write']);

        $headers = ['X-Offline-Request-Id' => 'offline-test-002'];
        $this->withHeaders($headers)->postJson('/api/v1/jabatan-desa', ['nama' => 'Kaur A', 'tingkat' => 'desa', 'aktif' => true])->assertCreated();
        $this->withHeaders($headers)->postJson('/api/v1/jabatan-desa', ['nama' => 'Kaur B', 'tingkat' => 'desa', 'aktif' => true])->assertConflict();

        $this->assertDatabaseCount('jabatan_desas', 1);
    }
}
