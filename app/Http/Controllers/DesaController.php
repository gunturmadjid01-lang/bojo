<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DesaController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('desa/index', [
            'desa' => Desa::query()->first(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('desa/form', [
            'desa' => Desa::query()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_desa' => ['nullable', 'string', 'max:50'],
            'nama_desa' => ['required', 'string', 'max:255'],
            'kecamatan' => ['nullable', 'string', 'max:255'],
            'kabupaten' => ['nullable', 'string', 'max:255'],
            'provinsi' => ['nullable', 'string', 'max:255'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'alamat_kantor' => ['nullable', 'string'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'aktif' => ['boolean'],
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('desa', 'public');
        }

        $desa = Desa::query()->firstOrNew();
        $desa->fill($validated);
        $desa->save();

        return to_route('desa.index')->with('success', 'Data desa berhasil disimpan.');
    }

    public function show(Desa $desa): Response
    {
        return Inertia::render('desa/show', [
            'desa' => $desa,
        ]);
    }

    public function edit(Desa $desa): Response
    {
        return Inertia::render('desa/form', [
            'desa' => $desa,
        ]);
    }

    public function update(Request $request, Desa $desa): RedirectResponse
    {
        $validated = $request->validate([
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
            if ($desa->logo && Storage::disk('public')->exists($desa->logo)) {
                Storage::disk('public')->delete($desa->logo);
            }
            $validated['logo'] = $request->file('logo')->store('desa', 'public');
        } elseif (! array_key_exists('logo', $validated)) {
            $validated['logo'] = $desa->logo;
        }

        $desa->fill($validated);
        $desa->save();

        return to_route('desa.show', $desa)->with('success', 'Data desa berhasil diperbarui.');
    }

    public function destroy(Desa $desa): RedirectResponse
    {
        if ($desa->logo && Storage::disk('public')->exists($desa->logo)) {
            Storage::disk('public')->delete($desa->logo);
        }

        $desa->delete();

        return to_route('desa.index')->with('success', 'Data desa berhasil dihapus.');
    }
}
