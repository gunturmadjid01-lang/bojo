<?php

namespace App\Http\Controllers\Api;

use App\Models\{Desa, Disabilitas, DisabilitasPenduduk, Dusun, JabatanDesa, KartuKeluarga, Kelahiran, Kematian, Pekerjaan, PenerimaBantuan, PendudukDatang, PendudukPindah, Pendidikan, PenugasanPerangkat, PenyaluranBantuan, ProgramBantuan, RT, RW, SosialEkonomiKeluarga, SosialEkonomiPenduduk};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ResourceController extends ApiController
{
    private const MODELS = [
        'desa' => Desa::class, 'dusun' => Dusun::class, 'rw' => RW::class, 'rt' => RT::class,
        'kelahiran' => Kelahiran::class, 'kematian' => Kematian::class, 'penduduk-datang' => PendudukDatang::class,
        'penduduk-pindah' => PendudukPindah::class, 'pendidikan' => Pendidikan::class, 'pekerjaan' => Pekerjaan::class,
        'jabatan-desa' => JabatanDesa::class, 'disabilitas' => Disabilitas::class,
        'sosial-ekonomi-keluarga' => SosialEkonomiKeluarga::class, 'sosial-ekonomi-penduduk' => SosialEkonomiPenduduk::class,
        'disabilitas-penduduk' => DisabilitasPenduduk::class, 'program-bantuan' => ProgramBantuan::class,
        'penerima-bantuan' => PenerimaBantuan::class, 'penyaluran-bantuan' => PenyaluranBantuan::class,
        'penugasan-perangkat' => PenugasanPerangkat::class,
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        $model = $this->model($request, $resource, 'view');
        $instance = new $model;
        $search = trim((string) $request->input('search', ''));
        $query = $model::query()->when($search !== '', fn ($q) => $q->where(function ($searchQuery) use ($search, $instance) {
            foreach (array_slice($instance->getFillable(), 0, 4) as $column) $searchQuery->orWhere($column, 'like', "%{$search}%");
        }))->latest();
        $this->applyFilters($request, $query, $instance);
        $this->loadRelations($query, $resource);
        return response()->json($query->paginate($this->perPage($request))->through(fn ($item) => $this->payload($item)));
    }

    public function show(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($request, $resource, 'view');
        $query = $model::query();
        $this->loadRelations($query, $resource);
        return response()->json(['data' => $this->payload($query->findOrFail((int) $id))]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $model = $this->model($request, $resource, 'create');
        if ($resource === 'penerima-bantuan' && $request->has('penerimas')) {
            $recipientTable = $request->input('jenis_penerima') === 'kartu_keluarga' ? 'kartu_keluargas' : 'penduduks';
            $data = $request->validate([
                'program_bantuan_id' => ['required', 'exists:program_bantuans,id'],
                'jenis_penerima' => ['required', 'in:penduduk,kartu_keluarga'],
                'penerimas' => ['required', 'array', 'min:1'],
                'penerimas.*.penerima_id' => ['required', 'integer', "exists:{$recipientTable},id"],
                'penerimas.*.tanggal_terdaftar' => ['required', 'date'],
                'penerimas.*.catatan' => ['nullable', 'string'],
            ]);
            $items = DB::transaction(function () use ($data) {
                return collect($data['penerimas'])->map(fn ($entry) => PenerimaBantuan::create([
                    'program_bantuan_id' => $data['program_bantuan_id'],
                    'penduduk_id' => $data['jenis_penerima'] === 'penduduk' ? $entry['penerima_id'] : null,
                    'kartu_keluarga_id' => $data['jenis_penerima'] === 'kartu_keluarga' ? $entry['penerima_id'] : null,
                    'tanggal_terdaftar' => $entry['tanggal_terdaftar'],
                    'status_kelayakan' => 'menunggu',
                    'status_verifikasi' => 'belum_diverifikasi',
                    'catatan' => $entry['catatan'] ?? null,
                ]));
            });
            return response()->json(['message' => $items->count() . ' penerima berhasil didaftarkan.'], 201);
        }
        $data = $this->input($request, $model);
        if ($model === Dusun::class) {
            $item = DB::transaction(function () use ($data) {
                $dusunData = collect($data)->except('rws')->all();
                if (empty($dusunData['kode'])) $dusunData['kode'] = $this->nextCode(Dusun::class, 'DSN');
                $dusun = Dusun::create($dusunData);
                $this->saveDusunHierarchy($dusun, $data['rws'] ?? []);
                return $dusun->load(['rws' => fn ($query) => $query->orderBy('nomor'), 'rws.rts' => fn ($query) => $query->orderBy('nomor')]);
            });
            return response()->json(['data' => $this->payload($item)], 201);
        }
        if ($model === Pendidikan::class && empty($data['kode'])) $data['kode'] = $this->nextCode(Pendidikan::class, 'PEND');
        if ($model === Pekerjaan::class && empty($data['kode'])) $data['kode'] = $this->nextCode(Pekerjaan::class, 'PKR');
        $item = $model::create($data);
        return response()->json(['data' => $this->payload($item)], 201);
    }

    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($request, $resource, 'edit');
        $item = $model::query()->findOrFail((int) $id);
        $data = $this->input($request, $model, $item);
        if ($model === Dusun::class) {
            DB::transaction(function () use ($item, $data): void {
                $item->update(collect($data)->except('rws')->all());
                $this->saveDusunHierarchy($item, $data['rws'] ?? []);
            });
            $item->load(['rws' => fn ($query) => $query->orderBy('nomor'), 'rws.rts' => fn ($query) => $query->orderBy('nomor')]);
        } else {
            $item->update($data);
        }
        return response()->json(['data' => $this->payload($item->refresh())]);
    }

    public function destroy(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($request, $resource, 'delete');
        $item = $model::query()->findOrFail((int) $id);
        $item->delete();
        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    private function model(Request $request, string $resource, string $action): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);
        $this->authorizeModule($request, $resource, $action);
        return self::MODELS[$resource];
    }

    private function input(Request $request, string $model, ?object $existing = null): array
    {
        $this->normalizeBooleans($request, ['aktif']);
        if ($model === Desa::class) {
            $data = $request->validate([
                'kode_desa' => ['nullable', 'string', 'max:50'],
                'nama_desa' => ['required', 'string', 'max:255'],
                'kecamatan' => ['nullable', 'string', 'max:255'],
                'kabupaten' => ['nullable', 'string', 'max:255'],
                'provinsi' => ['nullable', 'string', 'max:255'],
                'kode_pos' => ['nullable', 'string', 'max:10'],
                'alamat_kantor' => ['nullable', 'string'],
                'telepon' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
                'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'aktif' => ['boolean'],
            ]);
            if ($request->hasFile('logo')) {
                if ($existing?->logo && Storage::disk('public')->exists($existing->logo)) Storage::disk('public')->delete($existing->logo);
                $data['logo'] = $request->file('logo')->store('desa', 'public');
            } elseif ($existing && !array_key_exists('logo', $data)) {
                $data['logo'] = $existing->logo;
            }
            return $data;
        }
        if ($model === Dusun::class) {
            $data = $request->validate([
                'desa_id' => ['required', new \App\Rules\WilayahExists(Desa::class)], 'nama' => ['required', 'string', 'max:255'], 'aktif' => ['boolean'],
                'rws' => ['nullable', 'array'], 'rws.*.id' => ['nullable', 'integer'], 'rws.*.nomor' => ['required', 'string', 'max:10'], 'rws.*.aktif' => ['boolean'],
                'rws.*.rts' => ['nullable', 'array'], 'rws.*.rts.*.id' => ['nullable', 'integer'], 'rws.*.rts.*.nomor' => ['required', 'string', 'max:10'], 'rws.*.rts.*.aktif' => ['boolean'],
            ]);
            $rwNumbers = collect($data['rws'] ?? [])->pluck('nomor')->map(fn ($value) => mb_strtolower(trim($value)));
            $duplicateRwIndex = $rwNumbers->duplicates()->keys()->first();
            if ($duplicateRwIndex !== null) throw ValidationException::withMessages(["rws.$duplicateRwIndex.nomor" => 'Nomor RW ini sudah digunakan dalam Dusun yang sama.']);
            foreach ($data['rws'] ?? [] as $index => $rw) {
                $rtNumbers = collect($rw['rts'] ?? [])->pluck('nomor')->map(fn ($value) => mb_strtolower(trim($value)));
                $duplicateRtIndex = $rtNumbers->duplicates()->keys()->first();
                if ($duplicateRtIndex !== null) throw ValidationException::withMessages(["rws.$index.rts.$duplicateRtIndex.nomor" => 'Nomor RT ini sudah digunakan dalam RW yang sama.']);
            }
            return $data;
        }
        if ($model === RW::class) {
            return $request->validate(['dusun_id' => ['required', new \App\Rules\WilayahExists(Dusun::class)], 'nomor' => ['required', 'string', 'max:10'], 'aktif' => ['boolean']]);
        }
        if ($model === RT::class) {
            return $request->validate(['rw_id' => ['required', new \App\Rules\WilayahExists(RW::class)], 'nomor' => ['required', 'string', 'max:10'], 'aktif' => ['boolean']]);
        }
        if ($model === Pendidikan::class || $model === Pekerjaan::class) {
            return $request->validate(['nama' => ['required', 'string', 'max:255'], 'kode' => ['sometimes', 'nullable', 'string', 'max:50']]);
        }
        if ($model === PenyaluranBantuan::class) {
            $data = $request->validate([
                'penerima_bantuan_id' => ['required', new \App\Rules\WilayahExists(PenerimaBantuan::class)],
                'periode' => ['required', 'string', 'max:50'], 'tanggal_penyaluran' => ['required', 'date'],
                'jumlah' => ['required', 'numeric', 'min:0'], 'status' => ['required', 'in:belum_disalurkan,disalurkan,gagal'],
                'diterima_oleh' => ['nullable', 'string', 'max:255'], 'catatan' => ['nullable', 'string'],
            ]);
            $eligible = PenerimaBantuan::query()->whereKey($data['penerima_bantuan_id'])->where('status_verifikasi', 'terverifikasi')->where('status_kelayakan', 'layak')->exists();
            if (!$eligible) throw ValidationException::withMessages(['penerima_bantuan_id' => 'Penyaluran hanya dapat dibuat untuk penerima yang dinyatakan layak.']);
            $data['dibuat_oleh'] = $request->user()?->id;
            return $data;
        }
        $data = $request->only((new $model)->getFillable());
        if (!$data) throw ValidationException::withMessages(['data' => 'Minimal satu field harus dikirim.']);
        return $data;
    }

    private function normalizeBooleans(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if (!$request->has($field)) continue;
            $value = $request->input($field);
            if ($value === 'true' || $value === '1' || $value === 1 || $value === true) $request->merge([$field => true]);
            if ($value === 'false' || $value === '0' || $value === 0 || $value === false) $request->merge([$field => false]);
        }
    }

    private function nextCode(string $model, string $prefix): string
    {
        $lastCode = $model::query()->max('kode');
        preg_match('/(\d+)$/', (string) $lastCode, $matches);
        return $prefix . '-' . str_pad((string) (((int) ($matches[1] ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function loadRelations($query, string $resource): void
    {
        match ($resource) {
            'dusun' => $query->with(['rws' => fn ($relation) => $relation->orderBy('nomor'), 'rws.rts' => fn ($relation) => $relation->orderBy('nomor')]),
            'sosial-ekonomi-keluarga' => $query->with('kartuKeluarga:id,nomor_kk,alamat'),
            'sosial-ekonomi-penduduk' => $query->with('penduduk:id,nik,nama_lengkap'),
            'disabilitas-penduduk' => $query->with(['penduduk:id,nik,nama_lengkap', 'jenisDisabilitas:id,nama']),
            'penerima-bantuan' => $query->with(['program:id,nama', 'penduduk:id,nik,nama_lengkap', 'kartuKeluarga:id,nomor_kk,alamat']),
            'penyaluran-bantuan' => $query->with(['penerima.program:id,nama', 'penerima.penduduk:id,nik,nama_lengkap', 'penerima.kartuKeluarga:id,nomor_kk']),
            default => null,
        };
    }

    private function applyFilters(Request $request, $query, object $instance): void
    {
        foreach (['dusun_id', 'rw_id', 'rt_id'] as $field) {
            if (!$request->filled($field)) continue;
            if (in_array($field, $instance->getFillable(), true)) $query->where($field, $request->input($field));
            else {
                $relation = $field === 'dusun_id' ? 'dusun' : ($field === 'rw_id' ? 'rw' : 'rt');
                if (method_exists($instance, $relation)) $query->whereHas($relation, fn ($relationQuery) => $relationQuery->whereKey($request->input($field)));
                elseif (method_exists($instance, 'kartuKeluarga')) $query->whereHas('kartuKeluarga', fn ($relationQuery) => $relationQuery->where($field, $request->input($field)));
            }
        }
        $statusField = collect(['status', 'status_penduduk', 'status_verifikasi', 'status_kelayakan'])->first(fn ($field) => in_array($field, $instance->getFillable(), true));
        if ($statusField && $request->filled('status')) $query->where($statusField, $request->input('status'));
        $dateField = collect($instance->getFillable())->first(fn ($field) => str_starts_with($field, 'tanggal_'));
        if ($dateField && $request->filled('date_from')) $query->whereDate($dateField, '>=', $request->input('date_from'));
        if ($dateField && $request->filled('date_to')) $query->whereDate($dateField, '<=', $request->input('date_to'));
    }

    private function payload(object $item): array
    {
        $allowed = array_unique(array_merge(['id', 'created_at', 'updated_at'], $item->getFillable(), array_keys($item->getRelations())));
        return array_intersect_key($item->toArray(), array_flip($allowed));
    }

    private function saveDusunHierarchy(Dusun $dusun, array $rows): void
    {
        foreach ($rows as $rwIndex => $row) {
            $rw = isset($row['id']) ? $dusun->rws()->whereKey($row['id'])->first() : null;
            if (isset($row['id']) && ! $rw) throw ValidationException::withMessages(["rws.$rwIndex.id" => 'RW tidak termasuk dalam dusun ini.']);
            $rwData = ['nomor' => trim($row['nomor']), 'aktif' => $row['aktif'] ?? true];
            $rw ? $rw->update($rwData) : $rw = $dusun->rws()->create($rwData);
            foreach ($row['rts'] ?? [] as $rtIndex => $rtRow) {
                $rt = isset($rtRow['id']) ? $rw->rts()->whereKey($rtRow['id'])->first() : null;
                if (isset($rtRow['id']) && ! $rt) throw ValidationException::withMessages(["rws.$rwIndex.rts.$rtIndex.id" => 'RT tidak termasuk dalam RW ini.']);
                $rtData = ['nomor' => trim($rtRow['nomor']), 'aktif' => $rtRow['aktif'] ?? true];
                $rt ? $rt->update($rtData) : $rw->rts()->create($rtData);
            }
        }
    }
}
