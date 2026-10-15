<?php

namespace App\Http\Controllers;

use App\Models\Dusun;
use App\Models\RW;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RWController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('rw/index', [
            'rws' => RW::query()
                ->with('dusun:id,nama')
                ->latest()
                ->get(['id', 'dusun_id', 'nomor', 'aktif', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rw/form', [
            'rw' => null,
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $items = $request->input('entries', [$request->all()]);

        if (! is_array($items)) {
            $items = [$items];
        }

        $records = [];
        $errors = [];

        foreach ($items as $index => $item) {
            if (! is_array($item) || blank($item['nomor'] ?? null)) {
                continue;
            }

            $validator = Validator::make($item, [
                'dusun_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
                'nomor' => ['required', 'string', 'max:10'],
                'aktif' => ['boolean'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    $errors["entries.$index.$field"] = $messages[0];
                }
                continue;
            }

            $records[] = [
                'dusun_id' => $item['dusun_id'],
                'nomor' => $item['nomor'],
                'aktif' => $item['aktif'] ?? true,
            ];
        }

        if (empty($records)) {
            throw ValidationException::withMessages([
                'entries' => ['Minimal tambahkan satu data RW.'],
            ] + $errors);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($records as $record) {
            RW::create($record);
        }

        return to_route('rw.index')->with('success', count($records) . ' RW berhasil ditambahkan.');
    }

    public function show(RW $rw): Response
    {
        return Inertia::render('rw/show', [
            'rw' => $rw->load('dusun:id,nama'),
        ]);
    }

    public function edit(RW $rw): Response
    {
        return Inertia::render('rw/form', [
            'rw' => $rw->load('dusun:id,nama'),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, RW $rw): RedirectResponse
    {
        $validated = $request->validate([
            'dusun_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'nomor' => ['required', 'string', 'max:10'],
            'aktif' => ['boolean'],
        ]);

        $rw->update($validated);

        return to_route('rw.show', $rw)->with('success', 'RW berhasil diperbarui.');
    }

    public function destroy(RW $rw): RedirectResponse
    {
        $rw->delete();

        return to_route('rw.index')->with('success', 'RW berhasil dihapus.');
    }
}
