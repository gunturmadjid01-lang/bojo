<?php

namespace App\Http\Controllers;

use App\Models\AndroidAppRelease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AndroidAppReleaseController extends Controller
{
    public function show(Request $request): Response
    {
        $release = $this->latest();

        return Inertia::render('app-download', [
            'release' => $release ? $this->publicRelease($release) : null,
            'canManage' => $request->user()?->isSuperAdmin() ?? false,
        ]);
    }

    public function download(AndroidAppRelease $release): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($release->file_path), 404);

        return response()->download(
            Storage::disk('local')->path($release->file_path),
            'SPDBojo-'.$release->version.'.apk',
            [
                'Content-Type' => 'application/vnd.android.package-archive',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    public function index(Request $request): Response
    {
        $this->authorizeManager($request);

        return Inertia::render('android-releases/index', [
            'releases' => AndroidAppRelease::query()->latest('version_code')->get([
                'id', 'version', 'version_code', 'file_size', 'sha256', 'release_notes', 'is_latest', 'published_at',
            ]),
            'downloadUrl' => route('android.download'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManager($request);

        $data = $request->validate([
            'version' => ['required', 'string', 'max:40', 'regex:/^\\d+\\.\\d+\\.\\d+(?:[-+][A-Za-z0-9.-]+)?$/'],
            'version_code' => ['required', 'integer', 'min:1', Rule::unique('android_app_releases', 'version_code')],
            'release_notes' => ['nullable', 'string', 'max:10000'],
            'apk' => ['required', 'file', 'extensions:apk', 'max:512000'],
        ]);

        $latestBuildCode = (int) AndroidAppRelease::query()->max('version_code');
        if ((int) $data['version_code'] <= $latestBuildCode) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'version_code' => 'Kode build harus lebih besar dari rilis sebelumnya ('.$latestBuildCode.').',
            ]);
        }

        $file = $request->file('apk');
        $path = $file->store('android-releases', 'local');

        try {
            DB::transaction(function () use ($request, $data, $file, $path): void {
                AndroidAppRelease::query()->where('is_latest', true)->update(['is_latest' => false]);
                AndroidAppRelease::query()->create([
                    'version' => $data['version'],
                    'version_code' => $data['version_code'],
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'release_notes' => $data['release_notes'] ?? null,
                    'is_latest' => true,
                    'published_at' => now(),
                    'uploaded_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'APK Android terbaru berhasil dipublikasikan.');
    }

    public function latestApi(): \Illuminate\Http\JsonResponse
    {
        $release = $this->latest();

        return response()->json([
            'data' => $release ? $this->publicRelease($release) : null,
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    private function latest(): ?AndroidAppRelease
    {
        return AndroidAppRelease::query()->where('is_latest', true)->first();
    }

    private function publicRelease(AndroidAppRelease $release): array
    {
        return [
            'version' => $release->version,
            'version_code' => $release->version_code,
            'release_notes' => $release->release_notes,
            'file_size' => $release->file_size,
            'sha256' => $release->sha256,
            'published_at' => $release->published_at?->toIso8601String(),
            'download_url' => route('android.download'),
            'file_url' => route('android.release.file', $release),
        ];
    }

    private function authorizeManager(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Hanya Super Admin yang dapat mengelola rilis APK Android.');
    }
}
