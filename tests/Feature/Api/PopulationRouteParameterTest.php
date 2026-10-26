<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PopulationRouteParameterTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_routes_do_not_treat_record_id_as_module_name(): void
    {
        $actor = User::factory()->create();
        $modules = ['kelahiran', 'kematian', 'penduduk-datang', 'penduduk-pindah'];

        foreach ($modules as $module) {
            foreach (['view', 'edit', 'delete'] as $action) {
                $permission = Permission::findOrCreate("{$action} ".str_replace('-', ' ', $module));
                $actor->givePermissionTo($permission);
            }
        }

        Sanctum::actingAs($actor, ['api:read', 'api:write']);

        foreach ($modules as $module) {
            $this->getJson("/api/v1/{$module}/999999")->assertNotFound();
            $this->postJson("/api/v1/{$module}/999999", [])->assertNotFound();
            $this->deleteJson("/api/v1/{$module}/999999")->assertNotFound();
        }
    }
}
