<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Dusun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DusunController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dusun/index', [
            'dusuns' => Dusun::query()->with('desa:id,nama_desa')->latest()->get(['id', 'desa_id', 'kode', 'nama', 'aktif', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('dusun/form', ['dusun' => null, 'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateInput($request);
        $validated['kode'] = $this->generateKode();
        DB::transaction(function () use ($validated): void {
            $dusun = Dusun::create(collect($validated)->except('rws')->all());
            $this->saveHierarchy($dusun, $validated['rws'] ?? []);
        });
        return to_route('dusun.index')->with('success', 'Dusun beserta RW dan RT berhasil ditambahkan.');
    }

    public function show(Dusun $dusun): Response
    {
        return Inertia::render('dusun/show', ['dusun' => $this->loadHierarchy($dusun)]);
    }

    public function edit(Dusun $dusun): Response
    {
        return Inertia::render('dusun/form', ['dusun' => $this->loadHierarchy($dusun), 'desas' => Desa::query()->orderBy('nama_desa')->get(['id', 'nama_desa'])]);
    }

    public function update(Request $request, Dusun $dusun): RedirectResponse
    {
        $validated = $this->validateInput($request);
        DB::transaction(function () use ($dusun, $validated): void {
            $dusun->update(collect($validated)->except('rws')->all());
            $this->saveHierarchy($dusun, $validated['rws'] ?? []);
        });
        return to_route('dusun.show', $dusun)->with('success', 'Dusun beserta RW dan RT berhasil diperbarui.');
    }

    public function destroy(Dusun $dusun): RedirectResponse
    {
        $dusun->delete();
        return to_route('dusun.index')->with('success', 'Dusun berhasil dihapus.');
    }

    private function validateInput(Request $request): array
    {
        $validated = $request->validate([
            'desa_id' => ['required', new \App\Rules\WilayahExists(Desa::class)],
            'nama' => ['required', 'string', 'max:255'],
            'aktif' => ['boolean'],
            'rws' => ['nullable', 'array'],
            'rws.*.id' => ['nullable', 'integer'],
            'rws.*.nomor' => ['required', 'string', 'max:10'],
            'rws.*.aktif' => ['boolean'],
            'rws.*.rts' => ['nullable', 'array'],
            'rws.*.rts.*.id' => ['nullable', 'integer'],
            'rws.*.rts.*.nomor' => ['required', 'string', 'max:10'],
            'rws.*.rts.*.aktif' => ['boolean'],
        ]);
        $rwNumbers = collect($validated['rws'] ?? [])->pluck('nomor')->map(fn ($value) => mb_strtolower(trim($value)));
        if ($rwNumbers->duplicates()->isNotEmpty()) throw ValidationException::withMessages(['rws' => 'Nomor RW dalam satu dusun tidak boleh sama.']);
        foreach ($validated['rws'] ?? [] as $index => $rw) {
            $rtNumbers = collect($rw['rts'] ?? [])->pluck('nomor')->map(fn ($value) => mb_strtolower(trim($value)));
            if ($rtNumbers->duplicates()->isNotEmpty()) throw ValidationException::withMessages(["rws.$index.rts" => 'Nomor RT dalam satu RW tidak boleh sama.']);
        }
        return $validated;
    }

    private function saveHierarchy(Dusun $dusun, array $rows): void
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

    private function loadHierarchy(Dusun $dusun): Dusun
    {
        return $dusun->load(['desa:id,nama_desa', 'rws' => fn ($query) => $query->orderBy('nomor'), 'rws.rts' => fn ($query) => $query->orderBy('nomor')]);
    }

    private function generateKode(): string
    {
        $lastCode = Dusun::query()->max('kode');
        preg_match('/(\d+)$/', (string) $lastCode, $matches);
        return 'DSN-' . str_pad((string) (((int) ($matches[1] ?? 0)) + 1), 4, '0', STR_PAD_LEFT);
    }
}
