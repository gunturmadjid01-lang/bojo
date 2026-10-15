<?php

namespace App\Http\Controllers\Api;

use App\Models\{Desa, Dusun, JabatanDesa, PenugasanPerangkat, User};
use App\Rules\WilayahExists;
use App\Services\PenugasanPerangkatService;
use App\Support\ModulePermissions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerangkatDesaController extends ApiController
{
    public function __construct(private PenugasanPerangkatService $service) {}

    public function jabatanIndex(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'jabatan-desa');
        $items = JabatanDesa::query()
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($nested) => $nested
                ->where('nama', 'like', '%'.$request->string('search').'%')
                ->orWhere('kode', 'like', '%'.$request->string('search').'%')))
            ->latest()->paginate($this->perPage($request));

        return response()->json($items);
    }

    public function jabatanShow(Request $request, JabatanDesa $jabatanDesa): JsonResponse
    {
        $this->authorizeModule($request, 'jabatan-desa');
        return response()->json(['data' => $jabatanDesa]);
    }

    public function jabatanStore(Request $request): JsonResponse
    {
        $this->authorizeModule($request, 'jabatan-desa', 'create');
        $data = $request->validate($this->jabatanRules());
        $data['kode'] = $this->generateKode();

        return response()->json(['message' => 'Jabatan Desa berhasil ditambahkan.', 'data' => JabatanDesa::create($data)], 201);
    }

    public function jabatanUpdate(Request $request, JabatanDesa $jabatanDesa): JsonResponse
    {
        $this->authorizeModule($request, 'jabatan-desa', 'edit');
        abort_if($jabatanDesa->isSystemDefault(), 422, 'Jabatan bawaan sistem tidak dapat diubah.');
        $jabatanDesa->update($request->validate($this->jabatanRules()));

        return response()->json(['message' => 'Jabatan Desa berhasil diperbarui.', 'data' => $jabatanDesa->fresh()]);
    }

    public function jabatanDestroy(Request $request, JabatanDesa $jabatanDesa): JsonResponse
    {
        $this->authorizeModule($request, 'jabatan-desa', 'delete');
        abort_if($jabatanDesa->isSystemDefault(), 422, 'Jabatan bawaan sistem tidak dapat dihapus.');
        $jabatanDesa->delete();

        return response()->json(['message' => 'Jabatan Desa berhasil dihapus.']);
    }

    public function assignments(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request, 'view', ['lihat-penugasan-perangkat']);
        $items = $this->assignmentQuery()->aktif()
            ->when($request->filled('search'), fn ($query) => $query->whereHas('perangkatDesa.user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%')))
            ->latest('tanggal_mulai')->paginate($this->perPage($request));

        return response()->json($items);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request, 'view', ['lihat-riwayat-penugasan']);
        $items = $this->assignmentQuery()
            ->when($request->filled('search'), fn ($query) => $query->whereHas('perangkatDesa.user', fn ($user) => $user->where('name', 'like', '%'.$request->string('search').'%')))
            ->latest()->paginate($this->perPage($request));

        return response()->json($items);
    }

    public function assignmentShow(Request $request, PenugasanPerangkat $penugasanPerangkat): JsonResponse
    {
        $this->authorizeAssignment($request, 'view', ['lihat-penugasan-perangkat', 'lihat-riwayat-penugasan']);
        return response()->json(['data' => $penugasanPerangkat->load($this->assignmentRelations())]);
    }

    public function options(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request, 'create', ['tambah-penugasan-perangkat', 'ganti-penugasan-perangkat']);
        return response()->json([
            'jabatans' => JabatanDesa::where('aktif', true)->orderBy('nama')->get(['id', 'kode', 'nama', 'tingkat']),
            'users' => User::orderBy('name')->get(['id', 'name', 'username']),
            'desas' => Desa::where('aktif', true)->get(['id', 'nama_desa']),
            'dusuns' => Dusun::where('aktif', true)->orderBy('nama')->get(['id', 'desa_id', 'nama']),
        ]);
    }

    public function assignmentStore(Request $request): JsonResponse
    {
        $this->authorizeAssignment($request, 'create', ['tambah-penugasan-perangkat']);
        $assignment = $this->service->create($request->validate($this->assignmentRules()), $request->user());
        return response()->json(['message' => 'Penugasan perangkat desa berhasil disimpan.', 'data' => $assignment->load($this->assignmentRelations())], 201);
    }

    public function replace(Request $request, PenugasanPerangkat $penugasanPerangkat): JsonResponse
    {
        $this->authorizeAssignment($request, 'edit', ['ganti-penugasan-perangkat']);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'], 'tanggal_selesai_lama' => ['required', 'date'],
            'tanggal_mulai' => ['required', 'date', 'after:tanggal_selesai_lama'], 'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'nomor_sk_pengangkatan' => ['nullable', 'string'], 'tanggal_sk_pengangkatan' => ['nullable', 'date'],
            'nomor_sk_pemberhentian' => ['nullable', 'string'], 'tanggal_sk_pemberhentian' => ['nullable', 'date'], 'catatan' => ['nullable', 'string'],
        ]);
        $replacement = $this->service->replace($penugasanPerangkat, $data, $request->user());
        return response()->json(['message' => 'Pejabat berhasil diganti dan histori lama disimpan.', 'data' => $replacement->load($this->assignmentRelations())]);
    }

    public function end(Request $request, PenugasanPerangkat $penugasanPerangkat): JsonResponse
    {
        $this->authorizeAssignment($request, 'edit', ['akhiri-penugasan-perangkat']);
        $assignment = $this->service->end($penugasanPerangkat, $request->validate([
            'tanggal_selesai' => ['required', 'date'], 'nomor_sk_pemberhentian' => ['nullable', 'string'],
            'tanggal_sk_pemberhentian' => ['nullable', 'date'], 'catatan' => ['nullable', 'string'],
        ]));
        return response()->json(['message' => 'Penugasan berhasil diakhiri.', 'data' => $assignment->load($this->assignmentRelations())]);
    }

    private function authorizeAssignment(Request $request, string $action, array $special): void
    {
        abort_unless(ModulePermissions::allows($request->user(), 'penugasan-perangkat', $action) || ModulePermissions::hasAnyPermission($request->user(), $special), 403, 'Anda tidak memiliki izin untuk modul ini.');
        abort_unless($action === 'view' || $request->user()->tokenCan('api:write'), 403, 'Token tidak memiliki kemampuan menulis.');
    }

    private function assignmentQuery()
    {
        return PenugasanPerangkat::with($this->assignmentRelations());
    }

    private function assignmentRelations(): array
    {
        return ['perangkatDesa.user:id,name,username', 'jabatanDesa:id,kode,nama,tingkat', 'desa:id,nama_desa', 'dusun:id,nama'];
    }

    private function jabatanRules(): array
    {
        return ['nama' => ['required', 'string', 'max:255'], 'tingkat' => ['required', 'string', 'in:desa,dusun'], 'aktif' => ['boolean']];
    }

    private function assignmentRules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'], 'jabatan_desa_id' => ['required', 'exists:jabatan_desas,id'],
            'desa_id' => ['required', new WilayahExists(Desa::class)], 'dusun_id' => ['nullable', new WilayahExists(Dusun::class)],
            'tanggal_mulai' => ['required', 'date'], 'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'nomor_sk_pengangkatan' => ['nullable', 'string', 'max:255'], 'tanggal_sk_pengangkatan' => ['nullable', 'date'], 'catatan' => ['nullable', 'string'],
        ];
    }

    private function generateKode(): string
    {
        $lastCode = JabatanDesa::query()->max('kode');
        $sequence = is_string($lastCode) && preg_match('/(\d+)$/', $lastCode, $matches) ? (int) $matches[1] + 1 : 1;
        return 'JBD-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
