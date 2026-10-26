<?php

namespace App\Http\Controllers;

use App\Models\Dusun;
use App\Models\Pekerjaan;
use App\Models\Penduduk;
use App\Models\Pendidikan;
use App\Models\RT;
use App\Models\RW;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PendudukController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('penduduk/index', [
            'penduduks' => Penduduk::query()
                ->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama'])
                ->latest()
                ->get([
                    'id',
                    'nik',
                    'nama_lengkap',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'status_perkawinan',
                    'pendidikan_id',
                    'pekerjaan_id',
                    'dusun_id',
                    'rw_id',
                    'rt_id',
                    'status_penduduk',
                    'masih_hidup',
                    'created_at',
                ]),
            ...app(\App\Services\StatistikKependudukanService::class)->indexPayload('penduduk', $request),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('penduduk/form', [
            'penduduk' => null,
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
            'kartuKeluargas' => \App\Models\KartuKeluarga::query()->with('kepalaKeluarga.penduduk:id,nama_lengkap')->orderBy('nomor_kk')->get(['id', 'nomor_kk', 'alamat']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:16', 'unique:penduduks,nik'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'status_penduduk' => ['required', 'in:aktif,pindah,meninggal,sementara'],
            'masih_hidup' => ['boolean'],
            'foto' => ['nullable', 'string', 'max:255'],
            'penduduks' => ['nullable', 'array'],
            'penduduks.*.nik' => ['required', 'string', 'max:16', 'distinct', 'unique:penduduks,nik'],
            'penduduks.*.nama_lengkap' => ['required', 'string', 'max:255'],
            'penduduks.*.tempat_lahir' => ['nullable', 'string', 'max:255'],
            'penduduks.*.tanggal_lahir' => ['nullable', 'date'],
            'penduduks.*.jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'penduduks.*.hubungan_keluarga' => ['required', 'string', 'max:50', 'not_in:Kepala Keluarga'],
            'kartu_keluarga_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\KartuKeluarga::class)],
            'nomor_kk' => ['nullable', 'string', 'max:16', 'unique:kartu_keluargas,nomor_kk'],
            'tanggal_diterbitkan' => ['nullable', 'date'],
            'hubungan_keluarga_utama' => ['nullable', 'string', 'max:50'],
        ]);

        if (collect($validated['penduduks'] ?? [])->contains(fn (array $person) => $person['nik'] === $validated['nik'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['penduduks' => 'NIK penduduk tambahan tidak boleh sama dengan NIK penduduk utama.']);
        }
        if (! empty($validated['nomor_kk']) && empty($validated['dusun_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['dusun_id' => 'Dusun wajib dipilih ketika membuat KK baru.']);
        }

        DB::transaction(function () use ($validated): void {
            $additional = $validated['penduduks'] ?? [];
            $existingKkId = $validated['kartu_keluarga_id'] ?? null;
            $newKkNumber = $validated['nomor_kk'] ?? null;
            $mainRelation = $validated['hubungan_keluarga_utama'] ?? ($existingKkId ? 'Lainnya' : 'Kepala Keluarga');
            unset($validated['penduduks'], $validated['kartu_keluarga_id'], $validated['nomor_kk'], $validated['tanggal_diterbitkan'], $validated['hubungan_keluarga_utama']);

            $head = Penduduk::create($validated);
            $members = collect($additional)->map(function (array $person) use ($validated): array {
                $relation = $person['hubungan_keluarga'];
                unset($person['hubungan_keluarga']);
                $person = array_merge([
                    'kewarganegaraan' => 'WNI',
                    'status_penduduk' => 'aktif',
                    'masih_hidup' => true,
                ], $person, collect($validated)->only(['alamat', 'dusun_id', 'rw_id', 'rt_id'])->all());
                return ['penduduk' => Penduduk::create($person), 'hubungan' => $relation];
            });

            $kk = $existingKkId ? \App\Models\KartuKeluarga::findOrFail($existingKkId) : null;
            if (! $kk && $newKkNumber) {
                $desaId = \App\Models\Dusun::findOrFail($validated['dusun_id'])->desa_id;
                $kk = \App\Models\KartuKeluarga::create([
                    'nomor_kk' => $newKkNumber, 'desa_id' => $desaId,
                    'dusun_id' => $validated['dusun_id'], 'rw_id' => $validated['rw_id'] ?? null,
                    'rt_id' => $validated['rt_id'] ?? null, 'alamat' => $validated['alamat'] ?? '-',
                    'tanggal_diterbitkan' => request('tanggal_diterbitkan'), 'status' => 'aktif',
                ]);
            }
            if ($kk) {
                \App\Models\AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'penduduk_id' => $head->id, 'hubungan_keluarga' => $mainRelation, 'tanggal_masuk' => now()->toDateString(), 'aktif' => true]);
                foreach ($members as $member) \App\Models\AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'penduduk_id' => $member['penduduk']->id, 'hubungan_keluarga' => $member['hubungan'], 'tanggal_masuk' => now()->toDateString(), 'aktif' => true]);
            }
        });

        return to_route('penduduk.index')->with('success', 'Penduduk dan data keluarga berhasil ditambahkan.');
    }

    public function show(Penduduk $penduduk): Response
    {
        return Inertia::render('penduduk/show', [
            'penduduk' => $penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']),
        ]);
    }

    public function edit(Penduduk $penduduk): Response
    {
        return Inertia::render('penduduk/form', [
            'penduduk' => $penduduk->load(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']),
            'dusuns' => Dusun::query()->orderBy('nama')->get(['id', 'desa_id', 'nama']),
            'rws' => RW::query()->with('dusun:id,nama')->orderBy('nomor')->get(['id', 'dusun_id', 'nomor']),
            'rts' => RT::query()->with('rw:id,nomor')->orderBy('nomor')->get(['id', 'rw_id', 'nomor']),
            'pendidikans' => Pendidikan::query()->orderBy('nama')->get(['id', 'nama']),
            'pekerjaans' => Pekerjaan::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(Request $request, Penduduk $penduduk): RedirectResponse
    {
        $validated = $request->validate([
            'nik' => ['required', 'string', 'max:16', 'unique:penduduks,nik,' . $penduduk->id],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['nullable', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O'],
            'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
            'pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
            'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'],
            'kewarganegaraan' => ['required', 'string', 'max:50'],
            'nama_ayah' => ['nullable', 'string', 'max:255'],
            'nama_ibu' => ['nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string'],
            'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
            'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
            'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
            'status_penduduk' => ['required', 'in:aktif,pindah,meninggal,sementara'],
            'masih_hidup' => ['boolean'],
            'foto' => ['nullable', 'string', 'max:255'],
        ]);

        $penduduk->update($validated);

        return to_route('penduduk.show', $penduduk)->with('success', 'Penduduk berhasil diperbarui.');
    }

    public function destroy(Penduduk $penduduk): RedirectResponse
    {
        $penduduk->delete();

        return to_route('penduduk.index')->with('success', 'Penduduk berhasil dihapus.');
    }
}
