<?php

namespace App\Http\Controllers;

use App\Models\RT;
use App\Models\RW;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RTController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('rt/index', [
            'rts' => RT::query()
                ->with('rw:id,nomor')
                ->latest()
                ->get(['id', 'rw_id', 'nomor', 'aktif', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('rt/form', [
            'rt' => null,
            'rws' => RW::query()->orderBy('nomor')->get(['id', 'nomor']),
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
                'rw_id' => ['required', new \App\Rules\WilayahExists(\App\Models\RW::class)],
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
                'rw_id' => $item['rw_id'],
                'nomor' => $item['nomor'],
                'aktif' => $item['aktif'] ?? true,
            ];
        }

        if (empty($records)) {
            throw ValidationException::withMessages([
                'entries' => ['Minimal tambahkan satu data RT.'],
            ] + $errors);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        foreach ($records as $record) {
            RT::create($record);
        }

        return to_route('rt.index')->with('success', count($records) . ' RT berhasil ditambahkan.');
    }

    public function show(RT $rt): Response
    {
        return Inertia::render('rt/show', [
            'rt' => $rt->load('rw:id,nomor'),
        ]);
    }

    public function edit(RT $rt): Response
    {
        return Inertia::render('rt/form', [
            'rt' => $rt->load('rw:id,nomor'),
            'rws' => RW::query()->orderBy('nomor')->get(['id', 'nomor']),
        ]);
    }

    public function update(Request $request, RT $rt): RedirectResponse
    {
        $validated = $request->validate([
            'rw_id' => ['required', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'nomor' => ['required', 'string', 'max:10'],
            'aktif' => ['boolean'],
        ]);

        $rt->update($validated);

        return to_route('rt.show', $rt)->with('success', 'RT berhasil diperbarui.');
    }

    public function destroy(RT $rt): RedirectResponse
    {
        $rt->delete();

        return to_route('rt.index')->with('success', 'RT berhasil dihapus.');
    }
}
