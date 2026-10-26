<?php

namespace App\Http\Controllers\Api;

use App\Models\{AnggotaKartuKeluarga, KartuKeluarga, KedatanganPenduduk, Kelahiran, Kematian, Penduduk, PendudukPindah, PerpindahanPenduduk};
use App\Services\{KelahiranService, KematianService};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PopulationController extends ApiController
{
    private const MODELS = [
        'kartu-keluarga' => KartuKeluarga::class,
        'anggota-kartu-keluarga' => AnggotaKartuKeluarga::class,
        'penduduk' => Penduduk::class,
        'kelahiran' => Kelahiran::class,
        'kematian' => Kematian::class,
        'penduduk-datang' => KedatanganPenduduk::class,
        'penduduk-pindah' => PendudukPindah::class,
    ];

    public function index(Request $request, string $module): JsonResponse
    {
        $this->authorizeModule($request, $module);
        $model = $this->model($module);
        $query = $model::query()->latest();

        if ($module === 'penduduk') {
            $query->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']);
        } elseif ($module === 'kartu-keluarga') {
            $query->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga.penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        } elseif ($module === 'anggota-kartu-keluarga') {
            $query->with(['kartuKeluarga:id,nomor_kk,dusun_id,rw_id,rt_id', 'kartuKeluarga.dusun:id,nama', 'kartuKeluarga.rw:id,nomor', 'kartuKeluarga.rt:id,nomor', 'penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        } elseif ($module === 'kelahiran') {
            $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluarga:id,nomor_kk', 'ayah:id,nik,nama_lengkap', 'ibu:id,nik,nama_lengkap']);
        } elseif ($module === 'kematian') {
            $query->with('penduduk:id,nik,nama_lengkap');
        } elseif ($module === 'penduduk-datang') {
            $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluargaTujuan:id,nomor_kk,alamat,dusun_id,rw_id,rt_id', 'kartuKeluargaTujuan.anggotaKeluarga.penduduk:id,nik,nama_lengkap,jenis_kelamin', 'anggotaKedatangan.penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        } else {
            $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluargaAsal:id,nomor_kk', 'kartuKeluargaTujuan:id,nomor_kk']);
        }
        $this->applyFilters($request, $query, $module);

        return response()->json($query->paginate($this->perPage($request))->through(fn ($item) => $this->payload($module, $item)));
    }

    public function show(Request $request, string $module, string $id): JsonResponse
    {
        $this->authorizeModule($request, $module);
        $query = $this->model($module)::query();
        if ($module === 'penduduk') $query->with(['dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'pendidikan:id,nama', 'pekerjaan:id,nama']);
        if ($module === 'kartu-keluarga') $query->with(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga.penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        if ($module === 'anggota-kartu-keluarga') $query->with(['kartuKeluarga:id,nomor_kk,dusun_id,rw_id,rt_id', 'kartuKeluarga.dusun:id,nama', 'kartuKeluarga.rw:id,nomor', 'kartuKeluarga.rt:id,nomor', 'penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        if ($module === 'kelahiran') $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluarga:id,nomor_kk', 'ayah:id,nik,nama_lengkap', 'ibu:id,nik,nama_lengkap']);
        if ($module === 'kematian') $query->with('penduduk:id,nik,nama_lengkap');
        if ($module === 'penduduk-datang') $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluargaTujuan:id,nomor_kk,alamat,dusun_id,rw_id,rt_id', 'kartuKeluargaTujuan.anggotaKeluarga.penduduk:id,nik,nama_lengkap,jenis_kelamin', 'anggotaKedatangan.penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        if ($module === 'penduduk-pindah') $query->with(['penduduk:id,nik,nama_lengkap', 'kartuKeluargaTujuan:id,nomor_kk,alamat']);
        $item = $query->findOrFail((int) $id);
        return response()->json(['data' => $this->payload($module, $item)]);
    }

    public function store(Request $request, string $module): JsonResponse
    {
        $this->authorizeModule($request, $module, 'create');
        if ($module === 'anggota-kartu-keluarga' && $request->has('anggotas')) {
            $data = $request->validate([
                'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(KartuKeluarga::class)],
                'anggotas' => ['required', 'array', 'min:1'],
                'anggotas.*.penduduk_id' => ['required', 'integer', 'distinct', new \App\Rules\WilayahExists(Penduduk::class)],
                'anggotas.*.hubungan_keluarga' => ['required', 'string', 'max:50'],
                'anggotas.*.tanggal_masuk' => ['required', 'date'],
            ]);
            $item = DB::transaction(function () use ($data) {
                $ids = collect($data['anggotas'])->pluck('penduduk_id');
                if (AnggotaKartuKeluarga::query()->whereIn('penduduk_id', $ids)->where('aktif', true)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['anggotas' => 'Salah satu penduduk sudah terdaftar sebagai anggota aktif pada kartu keluarga lain.']);
                }
                $newHeads = collect($data['anggotas'])->where('hubungan_keluarga', 'Kepala Keluarga')->count();
                $hasHead = AnggotaKartuKeluarga::query()->where('kartu_keluarga_id', $data['kartu_keluarga_id'])->where('hubungan_keluarga', 'Kepala Keluarga')->where('aktif', true)->exists();
                if ($newHeads > 1 || ($newHeads === 1 && $hasHead)) throw ValidationException::withMessages(['anggotas' => 'Satu kartu keluarga hanya boleh memiliki satu kepala keluarga aktif.']);
                foreach ($data['anggotas'] as $member) AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $data['kartu_keluarga_id'], 'penduduk_id' => $member['penduduk_id'], 'hubungan_keluarga' => $member['hubungan_keluarga'], 'tanggal_masuk' => $member['tanggal_masuk'], 'aktif' => true]);
                return AnggotaKartuKeluarga::query()->latest('id')->first();
            });
            return response()->json(['data' => $this->payload($module, $item)], 201);
        }
        $access = app(\App\Services\WilayahAccess::class);
        $data = $access->forUser($request->user(), fn () => $this->validated($request, $module));

        $item = $access->forUser($request->user(), fn () => match ($module) {
            'kelahiran' => app(KelahiranService::class)->store($data, $request->user()),
            'kematian' => app(KematianService::class)->store($data, $request->user()),
            'penduduk-datang' => app(\App\Services\IndividualMigrationService::class)->arrival($data, $request->user()),
            'penduduk-pindah' => app(\App\Services\IndividualMigrationService::class)->departure($data, $request->user()),
            'kartu-keluarga' => $this->storeKartuKeluarga($data),
            'penduduk' => Penduduk::create($data),
            default => $this->model($module)::create($data),
        });

        return response()->json(['data' => $this->payload($module, $item)], 201);
    }

    public function update(Request $request, string $module, string $id): JsonResponse
    {
        $this->authorizeModule($request, $module, 'edit');
        return app(\App\Services\WilayahAccess::class)->forUser($request->user(), function () use ($request, $module, $id): JsonResponse {
            $model = $this->model($module);
            $item = $model::query()->find((int) $id);
            if (! $item) {
                $existsOutsideScope = $model::withoutGlobalScope('wilayah')->whereKey((int) $id)->exists();
                abort($existsOutsideScope ? 403 : 404, $existsOutsideScope
                    ? 'Data berada di luar wilayah penugasan akun Android ini.'
                    : 'Data yang akan diedit tidak ditemukan di server.');
            }
            $data = $this->validated($request, $module, $item);

            if ($module === 'kematian') {
                $item = app(KematianService::class)->update($item, $data, $request->user());
            } elseif ($module === 'penduduk-datang') {
                $personData = $data['penduduk'];
                unset($data['penduduk']);
                $item->penduduk()->update($personData);
                $item->update($data);
            } else {
                $item->update($data);
            }

            return response()->json(['data' => $this->payload($module, $item->refresh())]);
        });
    }

    public function destroy(Request $request, string $module, string $id): JsonResponse
    {
        $this->authorizeModule($request, $module, 'delete');
        $item = $this->model($module)::query()->findOrFail((int) $id);
        $item->delete();
        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    private function model(string $module): string
    {
        abort_unless(isset(self::MODELS[$module]), 404);
        return self::MODELS[$module];
    }

    private function applyFilters(Request $request, $query, string $module): void
    {
        $direct = ['penduduk' => 'penduduk', 'kartu-keluarga' => null, 'anggota-kartu-keluarga' => 'kartuKeluarga', 'kelahiran' => 'kartuKeluarga', 'kematian' => 'penduduk', 'penduduk-datang' => 'penduduk', 'penduduk-pindah' => 'penduduk'][$module] ?? null;
        foreach (['dusun_id', 'rw_id', 'rt_id'] as $field) if ($request->filled($field)) {
            if ($module === 'penduduk' || $module === 'kartu-keluarga') $query->where($field, $request->input($field));
            elseif ($direct) $query->whereHas($direct, fn ($relationQuery) => $relationQuery->where($field, $request->input($field)));
        }
        $statusField = match ($module) { 'penduduk' => 'status_penduduk', 'kartu-keluarga' => 'status', default => 'status' };
        if ($request->filled('status')) $query->where($statusField, $request->input('status'));
        $dateField = match ($module) { 'penduduk-datang' => 'tanggal_datang', 'penduduk-pindah' => 'tanggal_pindah', 'kematian' => 'tanggal_meninggal', 'kelahiran' => 'tanggal_lahir', default => null };
        if ($dateField && $request->filled('date_from')) $query->whereDate($dateField, '>=', $request->input('date_from'));
        if ($dateField && $request->filled('date_to')) $query->whereDate($dateField, '<=', $request->input('date_to'));
    }

    private function validated(Request $request, string $module, ?object $existing = null): array
    {
        $this->normalizeBooleans($request, ['masih_hidup', 'aktif']);
        return match ($module) {
            'penduduk' => $request->validate([
                'nik' => ['required', 'string', 'max:16', Rule::unique('penduduks', 'nik')->ignore($existing?->id)], 'nama_lengkap' => ['required', 'string', 'max:255'],
                'tempat_lahir' => ['nullable', 'string', 'max:255'], 'tanggal_lahir' => ['nullable', 'date'], 'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
                'agama' => ['nullable', 'string', 'max:30'], 'golongan_darah' => ['nullable', 'in:A,B,AB,O'], 'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
                'pendidikan_id' => ['nullable', 'exists:pendidikans,id'], 'pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'], 'kewarganegaraan' => ['required', 'string', 'max:50'],
                'nama_ayah' => ['nullable', 'string', 'max:255'], 'nama_ibu' => ['nullable', 'string', 'max:255'], 'telepon' => ['nullable', 'string', 'max:50'], 'alamat' => ['nullable', 'string'],
                'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)], 'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)], 'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
                'status_penduduk' => ['required', 'in:aktif,pindah,meninggal,sementara'], 'masih_hidup' => ['boolean'], 'foto' => ['nullable', 'string', 'max:255'],
            ]),
            'kartu-keluarga' => $request->validate([
                'desa_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Desa::class)],
                'dusun_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
                'rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)],
                'rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
                'penduduk_id' => [Rule::requiredIf(!$existing), 'nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)], 'nomor_kk' => ['required', 'string', 'max:16', Rule::unique('kartu_keluargas', 'nomor_kk')->ignore($existing?->id)],
                'alamat' => ['required', 'string'], 'kode_pos' => ['nullable', 'string', 'max:10'],
                'tanggal_diterbitkan' => ['nullable', 'date'], 'status' => ['required', 'in:aktif,nonaktif'],
                'anggotas' => ['nullable', 'array'],
                'anggotas.*.penduduk_id' => ['required', 'integer', 'distinct', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
                'anggotas.*.hubungan_keluarga' => ['required', 'string', 'max:50', 'not_in:Kepala Keluarga'],
                'anggotas.*.tanggal_masuk' => ['required', 'date'],
            ]),
            'anggota-kartu-keluarga' => $request->validate([
                'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(KartuKeluarga::class)],
                'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
                'hubungan_keluarga' => ['required', 'string', 'max:50'], 'tanggal_masuk' => ['nullable', 'date'],
                'tanggal_keluar' => ['nullable', 'date'], 'aktif' => ['nullable', 'boolean'], 'alasan_keluar' => ['nullable', 'string', 'max:255'],
            ]),
            'kelahiran' => $request->validate([
                'penduduk_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
                'kartu_keluarga_id' => ['required', new \App\Rules\WilayahExists(KartuKeluarga::class)],
                'ayah_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)], 'ibu_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)],
                'nama_lengkap' => ['nullable', 'required_without:penduduk_id', 'string', 'max:255'], 'nik' => ['nullable', 'string', 'max:16'],
                'jenis_kelamin' => ['nullable', 'required_without:penduduk_id', 'in:Laki-laki,Perempuan'], 'tempat_lahir' => ['nullable', 'required_without:penduduk_id', 'string', 'max:255'],
                'tanggal_lahir' => ['nullable', 'required_without:penduduk_id', 'date'], 'waktu_lahir' => ['nullable', 'date_format:H:i:s'],
                'golongan_darah' => ['nullable', 'in:A,B,AB,O'], 'status_perkawinan' => ['nullable', 'in:Belum Kawin,Kawin,Cerai Hidup,Cerai Mati'],
                'kewarganegaraan' => ['nullable', 'string', 'max:50'], 'nomor_akta_kelahiran' => ['nullable', 'string', 'max:255'], 'tanggal_akta_kelahiran' => ['nullable', 'date'],
                'anak_ke' => ['nullable', 'integer', 'min:1'], 'berat_lahir' => ['nullable', 'numeric', 'min:0'], 'panjang_lahir' => ['nullable', 'numeric', 'min:0'],
                'tanggal_dilaporkan' => ['nullable', 'date'], 'catatan' => ['nullable', 'string'],
            ]),
            'kematian' => $request->validate([
                'penduduk_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Penduduk::class)], 'tanggal_meninggal' => ['required', 'date'],
                'waktu_meninggal' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'], 'tempat_meninggal' => ['nullable', 'string', 'max:255'],
                'penyebab_meninggal' => ['nullable', 'string', 'max:255'], 'nomor_surat_kematian' => ['nullable', 'string', 'max:255'],
                'tanggal_dilaporkan' => ['nullable', 'date'], 'catatan' => ['nullable', 'string'],
            ]),
            'penduduk-datang' => $request->validate([
                'tanggal_datang' => ['required', 'date'],
                'penduduk' => ['required', 'array'], 'penduduk.nik' => ['required', 'string', 'max:16', Rule::unique('penduduks', 'nik')->ignore($existing?->penduduk_id)],
                'penduduk.nama_lengkap' => ['required', 'string', 'max:255'], 'penduduk.jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
                'penduduk.alamat' => ['required', 'string'], 'penduduk.dusun_id' => ['required', new \App\Rules\WilayahExists(\App\Models\Dusun::class)],
                'penduduk.rw_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RW::class)], 'penduduk.rt_id' => ['nullable', new \App\Rules\WilayahExists(\App\Models\RT::class)],
                'penduduk.tempat_lahir' => ['nullable', 'string', 'max:255'], 'penduduk.tanggal_lahir' => ['nullable', 'date'],
                'penduduk.agama' => ['nullable', 'string', 'max:50'], 'penduduk.golongan_darah' => ['nullable', 'in:A,B,AB,O'],
                'penduduk.status_perkawinan' => ['nullable', 'string', 'max:50'], 'penduduk.pendidikan_id' => ['nullable', 'exists:pendidikans,id'],
                'penduduk.pekerjaan_id' => ['nullable', 'exists:pekerjaans,id'], 'penduduk.kewarganegaraan' => ['nullable', 'string', 'max:50'],
                'penduduk.nama_ayah' => ['nullable', 'string', 'max:255'], 'penduduk.nama_ibu' => ['nullable', 'string', 'max:255'],
                'penduduk.telepon' => ['nullable', 'string', 'max:50'],
            ] + $this->optionalPopulationFields()),
            'penduduk-pindah' => $request->validate([
                'tanggal_pindah' => ['required', 'date'],
                'penduduk_id' => ['required', 'integer', new \App\Rules\WilayahExists(Penduduk::class)],
            ] + $this->optionalPopulationFields()),
        };
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

    private function optionalPopulationFields(): array
    {
        return ['penduduk_id' => ['nullable', 'integer'], 'kartu_keluarga_tujuan_id' => ['nullable', 'integer'], 'kartu_keluarga_asal_id' => ['nullable', 'integer'], 'nomor_surat_pindah' => ['nullable', 'string', 'max:255'], 'alamat_sebelumnya' => ['nullable', 'string'], 'desa_sebelumnya' => ['nullable', 'string', 'max:255'], 'kecamatan_sebelumnya' => ['nullable', 'string', 'max:255'], 'kabupaten_sebelumnya' => ['nullable', 'string', 'max:255'], 'provinsi_sebelumnya' => ['nullable', 'string', 'max:255'], 'alamat_tujuan' => ['nullable', 'string'], 'desa_tujuan' => ['nullable', 'string', 'max:255'], 'kecamatan_tujuan' => ['nullable', 'string', 'max:255'], 'kabupaten_tujuan' => ['nullable', 'string', 'max:255'], 'provinsi_tujuan' => ['nullable', 'string', 'max:255'], 'jenis_tujuan_kk' => ['nullable', 'in:gabung_kk,kk_baru'], 'hubungan_keluarga' => ['nullable', 'string', 'max:50'], 'status' => ['nullable', 'string', 'max:30'], 'alasan' => ['nullable', 'string', 'max:255'], 'catatan' => ['nullable', 'string'], 'penduduk' => ['nullable', 'array'], 'anggota' => ['nullable', 'array'], 'kk_baru' => ['nullable', 'array'], 'domisili_tujuan' => ['nullable', 'array']];
    }

    private function storeKartuKeluarga(array $data): KartuKeluarga
    {
        return DB::transaction(function () use ($data): KartuKeluarga {
            $penduduk = \App\Models\Penduduk::query()->lockForUpdate()->findOrFail($data['penduduk_id']);
            if ($penduduk->status_penduduk !== 'aktif' || !$penduduk->masih_hidup) {
                throw ValidationException::withMessages(['penduduk_id' => 'Penduduk yang dipilih tidak dapat dijadikan kepala keluarga.']);
            }
            if (($data['dusun_id'] ?? null) !== null && (int) $penduduk->dusun_id !== (int) $data['dusun_id']) {
                throw ValidationException::withMessages(['penduduk_id' => 'Kepala keluarga harus berasal dari dusun yang dipilih pada kartu keluarga.']);
            }
            if (AnggotaKartuKeluarga::query()->where('penduduk_id', $penduduk->id)->where('aktif', true)->exists()) {
                throw ValidationException::withMessages(['penduduk_id' => 'Penduduk yang dipilih sudah terdaftar aktif pada kartu keluarga lain.']);
            }
            $anggotaTambahan = collect($data['anggotas'] ?? []);
            $ids = $anggotaTambahan->pluck('penduduk_id');
            if ($ids->contains($penduduk->id) || AnggotaKartuKeluarga::query()->whereIn('penduduk_id', $ids)->where('aktif', true)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages(['anggotas' => 'Salah satu penduduk sudah dipilih sebagai kepala atau anggota aktif KK lain.']);
            }
            $calonAnggota = Penduduk::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
            foreach ($anggotaTambahan as $index => $anggota) {
                $calon = $calonAnggota->get($anggota['penduduk_id']);
                if (! $calon || $calon->status_penduduk !== 'aktif' || ! $calon->masih_hidup || (int) $calon->dusun_id !== (int) ($data['dusun_id'] ?? 0)) {
                    throw ValidationException::withMessages(["anggotas.$index.penduduk_id" => 'Anggota harus aktif, masih hidup, dan berasal dari dusun KK.']);
                }
            }
            $kk = KartuKeluarga::create(collect($data)->except(['penduduk_id', 'anggotas'])->all());
            AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'penduduk_id' => $penduduk->id, 'hubungan_keluarga' => 'Kepala Keluarga', 'tanggal_masuk' => now()->toDateString(), 'aktif' => true]);
            foreach ($anggotaTambahan as $anggota) AnggotaKartuKeluarga::create(['kartu_keluarga_id' => $kk->id, 'penduduk_id' => $anggota['penduduk_id'], 'hubungan_keluarga' => $anggota['hubungan_keluarga'], 'tanggal_masuk' => $anggota['tanggal_masuk'], 'aktif' => true]);
            return $kk->load(['desa:id,nama_desa', 'dusun:id,nama', 'rw:id,nomor', 'rt:id,nomor', 'anggotaKeluarga.penduduk:id,nik,nama_lengkap,jenis_kelamin']);
        });
    }

    private function payload(string $module, object $item): array
    {
        return match ($module) {
            'penduduk' => ['id' => $item->id, 'nik' => $item->nik, 'nama_lengkap' => $item->nama_lengkap, 'tempat_lahir' => $item->tempat_lahir, 'tanggal_lahir' => $item->tanggal_lahir?->toDateString(), 'jenis_kelamin' => $item->jenis_kelamin, 'agama' => $item->agama, 'golongan_darah' => $item->golongan_darah, 'status_perkawinan' => $item->status_perkawinan, 'pendidikan_id' => $item->pendidikan_id, 'pekerjaan_id' => $item->pekerjaan_id, 'pendidikan' => $item->pendidikan?->only(['id', 'nama']), 'pekerjaan' => $item->pekerjaan?->only(['id', 'nama']), 'kewarganegaraan' => $item->kewarganegaraan, 'nama_ayah' => $item->nama_ayah, 'nama_ibu' => $item->nama_ibu, 'telepon' => $item->telepon, 'alamat' => $item->alamat, 'dusun_id' => $item->dusun_id, 'rw_id' => $item->rw_id, 'rt_id' => $item->rt_id, 'wilayah' => ['dusun' => $item->dusun?->only(['id', 'nama']), 'rw' => $item->rw?->only(['id', 'nomor']), 'rt' => $item->rt?->only(['id', 'nomor'])], 'status_penduduk' => $item->status_penduduk, 'masih_hidup' => $item->masih_hidup],
            'kartu-keluarga' => ['id' => $item->id, 'nomor_kk' => $item->nomor_kk, 'alamat' => $item->alamat, 'kode_pos' => $item->kode_pos, 'tanggal_diterbitkan' => $item->tanggal_diterbitkan?->toDateString(), 'status' => $item->status, 'desa' => $item->desa?->only(['id', 'nama_desa']), 'dusun' => $item->dusun?->only(['id', 'nama']), 'rw' => $item->rw?->only(['id', 'nomor']), 'rt' => $item->rt?->only(['id', 'nomor']), 'anggota' => $item->anggotaKeluarga?->values()->all() ?? []],
            'anggota-kartu-keluarga' => ['id' => $item->id, 'hubungan_keluarga' => $item->hubungan_keluarga, 'tanggal_masuk' => $item->tanggal_masuk?->toDateString(), 'tanggal_keluar' => $item->tanggal_keluar?->toDateString(), 'aktif' => $item->aktif, 'alasan_keluar' => $item->alasan_keluar, 'kartu_keluarga' => $item->kartuKeluarga?->only(['id', 'nomor_kk']), 'penduduk' => $item->penduduk?->only(['id', 'nik', 'nama_lengkap', 'jenis_kelamin']), 'wilayah' => ['dusun' => $item->kartuKeluarga?->dusun?->only(['id', 'nama']), 'rw' => $item->kartuKeluarga?->rw?->only(['id', 'nomor']), 'rt' => $item->kartuKeluarga?->rt?->only(['id', 'nomor'])]],
            'kelahiran' => ['id' => $item->id, 'penduduk_id' => $item->penduduk_id, 'kartu_keluarga_id' => $item->kartu_keluarga_id, 'ayah_id' => $item->ayah_id, 'ibu_id' => $item->ibu_id, 'penduduk' => $item->penduduk?->only(['id', 'nik', 'nama_lengkap']), 'kartu_keluarga' => $item->kartuKeluarga?->only(['id', 'nomor_kk']), 'ayah' => $item->ayah?->only(['id', 'nik', 'nama_lengkap']), 'ibu' => $item->ibu?->only(['id', 'nik', 'nama_lengkap']), 'nama_lengkap' => $item->penduduk?->nama_lengkap, 'nik' => $item->penduduk?->nik, 'jenis_kelamin' => $item->penduduk?->jenis_kelamin, 'tempat_lahir' => $item->tempat_lahir, 'tanggal_lahir' => $item->tanggal_lahir?->toDateString(), 'waktu_lahir' => $item->waktu_lahir, 'nomor_akta_kelahiran' => $item->nomor_akta_kelahiran, 'tanggal_akta_kelahiran' => $item->tanggal_akta_kelahiran?->toDateString(), 'anak_ke' => $item->anak_ke, 'berat_lahir' => $item->berat_lahir, 'panjang_lahir' => $item->panjang_lahir, 'tanggal_dilaporkan' => $item->tanggal_dilaporkan?->toDateString(), 'catatan' => $item->catatan],
            'kematian' => ['id' => $item->id, 'penduduk_id' => $item->penduduk_id, 'penduduk' => $item->penduduk?->only(['id', 'nik', 'nama_lengkap']), 'tanggal_meninggal' => $item->tanggal_meninggal?->toDateString(), 'waktu_meninggal' => $item->waktu_meninggal, 'tempat_meninggal' => $item->tempat_meninggal, 'penyebab_meninggal' => $item->penyebab_meninggal, 'nomor_surat_kematian' => $item->nomor_surat_kematian, 'tanggal_dilaporkan' => $item->tanggal_dilaporkan?->toDateString(), 'catatan' => $item->catatan],
            'penduduk-datang' => $this->arrivalPayload($item),
            'penduduk-pindah' => ['id' => $item->id, 'penduduk_id' => $item->penduduk_id, 'penduduk' => $item->penduduk?->only(['id', 'nik', 'nama_lengkap']), 'jenis_pindah' => $item->jenis_pindah, 'jenis_tujuan_kk' => $item->jenis_tujuan_kk, 'kartu_keluarga_tujuan_id' => $item->kartu_keluarga_tujuan_id, 'kartu_keluarga_tujuan' => $item->kartuKeluargaTujuan?->only(['id', 'nomor_kk', 'alamat']), 'tanggal_pindah' => $item->tanggal_pindah?->toDateString(), 'alamat_tujuan' => $item->alamat_tujuan, 'desa_tujuan' => $item->desa_tujuan, 'kabupaten_tujuan' => $item->kabupaten_tujuan, 'status' => $item->status, 'catatan' => $item->catatan],
            default => array_merge(['id' => $item->id], collect($item->toArray())->except(['updated_at'])->all()),
        };
    }

    private function arrivalPayload(object $item): array
    {
        $members = $item->anggotaKedatangan ?? collect();
        $first = $members->first();
        $person = $item->penduduk ?? $first?->penduduk;
        return ['id' => $item->id, 'jenis_kedatangan' => $item->jenis_kedatangan, 'tanggal_datang' => $item->tanggal_datang?->toDateString(), 'penduduk_id' => $item->penduduk_id ?? $first?->penduduk_id, 'penduduk' => $person ? collect($person->toArray())->only(['id', 'nik', 'nama_lengkap', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'golongan_darah', 'status_perkawinan', 'pendidikan_id', 'pekerjaan_id', 'kewarganegaraan', 'nama_ayah', 'nama_ibu', 'telepon', 'alamat', 'dusun_id', 'rw_id', 'rt_id'])->all() : null, 'kartu_keluarga_tujuan_id' => $item->kartu_keluarga_tujuan_id, 'kartu_keluarga_tujuan' => $item->kartuKeluargaTujuan?->only(['id', 'nomor_kk', 'alamat', 'dusun_id', 'rw_id', 'rt_id']), 'anggota' => $members->map(fn ($member) => ['id' => $member->id, 'penduduk_id' => $member->penduduk_id, 'hubungan_keluarga' => $member->hubungan_keluarga, 'penduduk' => $member->penduduk?->only(['id', 'nik', 'nama_lengkap', 'jenis_kelamin'])])->values()->all(), 'anggota_kk' => $item->kartuKeluargaTujuan?->anggotaKeluarga?->map(fn ($member) => ['id' => $member->id, 'penduduk_id' => $member->penduduk_id, 'hubungan_keluarga' => $member->hubungan_keluarga, 'aktif' => $member->aktif, 'penduduk' => $member->penduduk?->only(['id', 'nik', 'nama_lengkap', 'jenis_kelamin'])])->values()->all() ?? [], 'jenis_tujuan_kk' => $item->jenis_tujuan_kk, 'nomor_surat_pindah' => $item->nomor_surat_pindah, 'alamat_sebelumnya' => $item->alamat_sebelumnya, 'desa_sebelumnya' => $item->desa_sebelumnya, 'kecamatan_sebelumnya' => $item->kecamatan_sebelumnya, 'kabupaten_sebelumnya' => $item->kabupaten_sebelumnya, 'provinsi_sebelumnya' => $item->provinsi_sebelumnya, 'status' => $item->status, 'catatan' => $item->catatan];
    }
}
