<?php

namespace App\Http\Middleware;

use App\Models\Desa;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return array_merge(parent::share($request), [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => function () use ($request) {
                    $user = $request->user();

                    if (!$user) {
                        return null;
                    }

                    $user->load(['roles:id,name', 'permissions:id,name']);

                    return array_merge($user->toArray(), [
                        'roles' => $user->roles->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values()->all(),
                        'permissions' => $user->getAllPermissions()->map(fn ($permission) => ['id' => $permission->id, 'name' => $permission->name])->values()->all(),
                        'role_names' => $user->getRoleNames()->values()->all(),
                        'permission_names' => $user->getPermissionNames()->values()->all(),
                    ]);
                },
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'desa' => fn () => Desa::query()->where('aktif', true)->first([
                'id', 'nama_desa', 'kecamatan', 'kabupaten', 'provinsi', 'logo',
            ]),
        ]);
    }
}
