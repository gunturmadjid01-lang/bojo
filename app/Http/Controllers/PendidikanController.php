<?php

namespace App\Http\Controllers;

use App\Models\Pendidikan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PendidikanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('pendidikan/index', [
            'pendidikans' => Pendidikan::query()->latest()->get(['id', 'kode', 'nama', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('pendidikan/form', [
            'pendidikan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $validated['kode'] = $this->generateKode();

        Pendidikan::create($validated);

        return to_route('pendidikan.index')->with('success', 'Pendidikan berhasil ditambahkan.');
    }

    public function show(Pendidikan $pendidikan): Response
    {
        return Inertia::render('pendidikan/show', [
            'pendidikan' => $pendidikan,
        ]);
    }

    public function edit(Pendidikan $pendidikan): Response
    {
        return Inertia::render('pendidikan/form', [
            'pendidikan' => $pendidikan,
        ]);
    }

    public function update(Request $request, Pendidikan $pendidikan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $pendidikan->update($validated);

        return to_route('pendidikan.show', $pendidikan)->with('success', 'Pendidikan berhasil diperbarui.');
    }

    public function destroy(Pendidikan $pendidikan): RedirectResponse
    {
        $pendidikan->delete();

        return to_route('pendidikan.index')->with('success', 'Pendidikan berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $lastCode = Pendidikan::query()->max('kode');
        $sequence = 1;

        if (is_string($lastCode) && preg_match('/(\d+)$/', $lastCode, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PEND-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
