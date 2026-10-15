<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_matrix_page_renders_and_updates_role_permissions(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        $role->syncPermissions([]);

        foreach (['view permissions', 'edit permissions'] as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $role->givePermissionTo(['view permissions', 'edit permissions']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/permissions');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('permissions/index')
                ->has('roles')
                ->has('permissionGroups')
                ->where('permissionGroups.0.group', 'Master Data')
                ->where('permissionGroups.1.group', 'Master Data')
                ->where('permissionGroups.0.title', 'Desa'));

        $response = $this->actingAs($user)->post('/permissions', [
            'role_id' => $role->id,
            'permissions' => [
                'view dusun' => true,
                'create dusun' => true,
                'edit dusun' => true,
                'delete dusun' => true,
            ],
        ]);

        $response->assertRedirect('/permissions');

        $this->assertTrue($role->fresh()->hasPermissionTo('view dusun'));
        $this->assertTrue($role->fresh()->hasPermissionTo('create dusun'));
        $this->assertTrue($role->fresh()->hasPermissionTo('edit dusun'));
        $this->assertTrue($role->fresh()->hasPermissionTo('delete dusun'));
    }

    public function test_inertia_user_includes_permissions_from_assigned_roles(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        Permission::firstOrCreate(['name' => 'view dusuns', 'guard_name' => 'web']);
        $role->syncPermissions(['view dusuns']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('auth.user.permissions.0.name', 'view dusuns'));
    }

    public function test_saving_matrix_removes_legacy_aliases_but_preserves_special_permissions(): void
    {
        $role = Role::firstOrCreate(['name' => 'kepala_dusun']);
        foreach (['view permissions', 'edit permissions', 'view dusun', 'view dusuns', 'lihat-riwayat-penugasan'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        $role->syncPermissions(['view permissions', 'edit permissions', 'view dusun', 'view dusuns', 'lihat-riwayat-penugasan']);

        $user = User::factory()->create();
        $user->givePermissionTo(['view permissions', 'edit permissions']);

        $this->actingAs($user)->post('/permissions', [
            'role_id' => $role->id,
            'permissions' => [],
        ])->assertRedirect('/permissions');

        $names = $role->fresh()->permissions()->pluck('name');
        $this->assertFalse($names->contains('view dusun'));
        $this->assertFalse($names->contains('view dusuns'));
        $this->assertTrue($names->contains('lihat-riwayat-penugasan'));
    }
}
