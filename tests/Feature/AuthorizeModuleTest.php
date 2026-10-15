<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizeModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_view_permission_cannot_open_module_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dusun')->assertForbidden();
    }

    public function test_view_permission_does_not_grant_create_access(): void
    {
        $role = Role::create(['name' => 'operator']);
        Permission::create(['name' => 'view dusuns', 'guard_name' => 'web']);
        $role->givePermissionTo('view dusuns');

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/dusun')->assertOk();
        $this->actingAs($user)->get('/dusun/create')->assertForbidden();
    }

    public function test_permission_aliases_are_supported_for_module_routes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::create([
            'name' => 'view kartu keluarga',
            'guard_name' => 'web',
        ]));

        $this->actingAs($user)->get('/kartu-keluarga')->assertOk();
    }

    public function test_reports_follow_permission_instead_of_hard_coded_role(): void
    {
        $role = Role::create(['name' => 'operator_laporan']);
        Permission::create(['name' => 'view reports', 'guard_name' => 'web']);
        $role->givePermissionTo('view reports');

        $allowedUser = User::factory()->create();
        $allowedUser->assignRole($role);

        $blockedUser = User::factory()->create();

        $this->actingAs($allowedUser)->get('/laporan')->assertOk();
        $this->actingAs($blockedUser)->get('/laporan')->assertForbidden();
    }

    public function test_super_admin_role_name_variants_bypass_module_permissions(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)->get('/users')->assertOk();
    }
}
