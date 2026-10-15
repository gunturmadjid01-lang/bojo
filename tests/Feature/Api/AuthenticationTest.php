<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejects_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'android-test',
        ]);

        $response->assertStatus(422)->assertJsonMissingPath('token');
    }

    public function test_api_token_can_read_profile_and_is_revoked_on_logout(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $login = $this->postJson('/api/v1/auth/login', [
            'login' => $user->username,
            'password' => 'password',
            'device_name' => 'android-test',
        ])->assertOk();

        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('user.id', $user->id);
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_dashboard_requires_api_token(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }
}
