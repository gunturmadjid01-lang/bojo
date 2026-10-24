<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccessPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_api_requires_matching_action_permission(): void
    {
        $actor = User::factory()->create();
        $target = User::factory()->create();
        Permission::findOrCreate('view users');
        $actor->givePermissionTo('view users');
        Sanctum::actingAs($actor, ['api:read', 'api:write']);

        $this->getJson('/api/v1/access/users')->assertOk();
        $this->postJson('/api/v1/access/users', [])->assertForbidden();
        $this->putJson("/api/v1/access/users/{$target->id}", [])->assertForbidden();
        $this->deleteJson("/api/v1/access/users/{$target->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_user_without_view_permission_cannot_open_users_api(): void
    {
        $actor = User::factory()->create();
        Sanctum::actingAs($actor, ['api:read', 'api:write']);

        $this->getJson('/api/v1/access/users')->assertForbidden();
    }
}
