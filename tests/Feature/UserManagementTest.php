<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_create_form_and_create_account_with_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('sekdes');

        $this->actingAs($admin)
            ->get('/users/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('users/form')
                ->where('user', null)
                ->has('roles', 4));

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Operator Desa',
            'username' => 'operator.desa',
            'email' => 'operator@bojo.local',
            'jenis_kelamin' => 'Perempuan',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'kepala_dusun',
        ]);

        $response->assertRedirect('/users');

        $user = User::query()->where('username', 'operator.desa')->firstOrFail();
        $this->assertTrue($user->hasRole('kepala_dusun'));
    }

    public function test_user_without_create_users_permission_cannot_create_account(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('kepala_desa');

        $this->actingAs($user)->get('/users/create')->assertForbidden();
        $this->actingAs($user)->post('/users', [])->assertForbidden();
    }

    public function test_user_permissions_are_available_in_permission_matrix(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(Role::findByName('super_admin'));

        $this->actingAs($admin)
            ->get('/permissions')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('permissionGroups', fn ($groups) => collect($groups)->contains(
                    fn ($group) => $group['group'] === 'Pengaturan Sistem'
                        && $group['title'] === 'Users'
                        && in_array('create users', $group['permissions'], true)
                )));
    }
}
