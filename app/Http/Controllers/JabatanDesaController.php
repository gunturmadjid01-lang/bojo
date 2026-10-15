<?php

namespace App\Http\Controllers;

use App\Models\JabatanDesa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JabatanDesaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('jabatan-desa/index', [
            'jabatanDesas' => JabatanDesa::query()->latest()->get(['id', 'kode', 'nama', 'tingkat', 'aktif', 'bawaan_sistem', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('jabatan-desa/form', [
            'jabatanDesa' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tingkat' => ['required', 'string', 'in:desa,dusun'],
            'aktif' => ['boolean'],
        ]);

        $validated['kode'] = $this->generateKode();

        JabatanDesa::create($validated);

        return to_route('jabatan-desa.index')->with('success', 'Jabatan Desa berhasil ditambahkan.');
    }

    public function show(JabatanDesa $jabatanDesa): Response
    {
        return Inertia::render('jabatan-desa/show', [
            'jabatanDesa' => $jabatanDesa,
        ]);
    }

    public function edit(JabatanDesa $jabatanDesa): Response
    {
        if ($jabatanDesa->isSystemDefault()) {
            return redirect()->route('jabatan-desa.index')->withErrors([
                'bawaan_sistem' => 'Jabatan bawaan sistem tidak dapat diubah.',
            ]);
        }

        return Inertia::render('jabatan-desa/form', [
            'jabatanDesa' => $jabatanDesa,
        ]);
    }

    public function update(Request $request, JabatanDesa $jabatanDesa): RedirectResponse
    {
        if ($jabatanDesa->isSystemDefault()) {
            return back()->withErrors([
                'bawaan_sistem' => 'Jabatan bawaan sistem tidak dapat diubah.',
            ]);
        }

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'tingkat' => ['required', 'string', 'in:desa,dusun'],
            'aktif' => ['boolean'],
        ]);

        $jabatanDesa->update($validated);

        return to_route('jabatan-desa.show', $jabatanDesa)->with('success', 'Jabatan Desa berhasil diperbarui.');
    }

    public function destroy(JabatanDesa $jabatanDesa): RedirectResponse
    {
        if ($jabatanDesa->isSystemDefault()) {
            return back()->withErrors([
                'bawaan_sistem' => 'Jabatan bawaan sistem tidak dapat dihapus.',
            ]);
        }

        $jabatanDesa->delete();

        return to_route('jabatan-desa.index')->with('success', 'Jabatan Desa berhasil dihapus.');
    }

    private function generateKode(): string
    {
        $lastCode = JabatanDesa::query()->max('kode');
        $sequence = 1;

        if (is_string($lastCode) && preg_match('/(\d+)$/', $lastCode, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }

        return 'JBD-' . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
