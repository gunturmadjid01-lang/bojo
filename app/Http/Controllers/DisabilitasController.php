<?php

namespace App\Http\Controllers;

use App\Models\Disabilitas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DisabilitasController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('disabilitas/index', [
            'disabilitas' => Disabilitas::query()->latest()->get(['id', 'nama', 'keterangan', 'aktif', 'created_at']),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('disabilitas', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('disabilitas/form', [
            'disabilitas' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ]);

        Disabilitas::create($validated);

        return to_route('disabilitas.index')->with('success', 'Jenis Disabilitas berhasil ditambahkan.');
    }

    public function show(Disabilitas $disabilitas): Response
    {
        return Inertia::render('disabilitas/show', [
            'disabilitas' => $disabilitas,
        ]);
    }

    public function edit(Disabilitas $disabilitas): Response
    {
        return Inertia::render('disabilitas/form', [
            'disabilitas' => $disabilitas,
        ]);
    }

    public function update(Request $request, Disabilitas $disabilitas): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ]);

        $disabilitas->update($validated);

        return to_route('disabilitas.show', $disabilitas)->with('success', 'Jenis Disabilitas berhasil diperbarui.');
    }

    public function destroy(Disabilitas $disabilitas): RedirectResponse
    {
        $disabilitas->delete();

        return to_route('disabilitas.index')->with('success', 'Jenis Disabilitas berhasil dihapus.');
    }
}
