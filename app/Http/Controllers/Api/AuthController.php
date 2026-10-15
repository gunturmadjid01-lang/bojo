<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);
        $key = Str::transliterate(Str::lower($data['login']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Terlalu banyak percobaan login. Silakan coba lagi nanti.']);
        }
        $user = User::query()->where(fn ($query) => $query->where('email', $data['login'])->orWhere('username', $data['login']))->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['login' => 'Kredensial tidak valid.']);
        }
        RateLimiter::clear($key);
        $token = $user->createToken($data['device_name'], ['api:read', 'api:write'])->plainTextToken;
        return response()->json(['token' => $token, 'user' => $this->userPayload($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();
        if ($currentToken && method_exists($currentToken, 'getKey')) {
            $request->user()->tokens()->whereKey($currentToken->getKey())->delete();
        }
        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('perangkatDesa.penugasanAktif.jabatanDesa');
        return [
            'id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar ? (Str::startsWith($user->avatar, ['http://', 'https://']) ? $user->avatar : url('storage/'.$user->avatar)) : null,
            'roles' => $user->getRoleNames()->values()->all(), 'permissions' => $user->getAllPermissions()->pluck('name')->values()->all(),
            'assignments' => $user->perangkatDesa?->penugasanAktif?->map(fn ($assignment) => [
                'desa_id' => $assignment->desa_id, 'dusun_id' => $assignment->dusun_id, 'tingkat' => $assignment->jabatanDesa?->tingkat,
            ])->values()->all() ?? [],
        ];
    }
}
