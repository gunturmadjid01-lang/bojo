<?php

namespace App\Http\Controllers;

use App\Models\Pekerjaan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PekerjaanController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('pekerjaan/index', [
            'pekerjaans' => Pekerjaan::query()->latest()->get(['id', 'kode', 'nama', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('pekerjaan/form', [
            'pekerjaan' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $validated['kode'] = $this->generateKode();

        Pekerjaan::create($validated);

        return to_route('pekerjaan.index')->with('success', 'Pekerjaan berhasil ditambahkan.');
    }

    public function show(Pekerjaan $pekerjaan): Response
    {
        return Inertia::render('pekerjaan/show', [
            'pekerjaan' => $pekerjaan,
        ]);
    }

    public function edit(Pekerjaan $pekerjaan): Response
    {
        return Inertia::render('pekerjaan/form', [
            'pekerjaan' => $pekerjaan,
        ]);
    }

    public function update(Request $request, Pekerjaan $pekerjaan): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $pekerjaan->update($validated);

        return to_route('pekerjaan.show', $pekerjaan)->with('success', 'Pekerjaan berhasil diperbarui.');
    }

    public function destroy(Pekerjaan $pekerjaan): RedirectResponse
    {
        $pekerjaan->delete();

        return to_route('pekerjaan.index')->with('success', 'Pekerjaan berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $lastCode = Pekerjaan::query()->max('kode');
        $sequence = 1;

        if (is_string($lastCode) && preg_match('/(\d+)$/', $lastCode, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'PKR-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
