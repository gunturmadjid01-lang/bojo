<?php

namespace App\Services;

use App\Models\AnggotaKartuKeluarga;
use App\Models\KartuKeluarga;
use App\Models\Kelahiran;
use App\Models\Penduduk;
use App\Models\PenugasanPerangkat;
use App\Models\PeristiwaKependudukan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KelahiranService
{
    public function store(array $data, ?User $actor = null): Kelahiran
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = $actor ?? auth()->user();
            $this->assertActiveAssignment($user);

            $kartuKeluarga = KartuKeluarga::query()
                ->with(['desa', 'dusun', 'rw', 'rt'])
                ->lockForUpdate()
                ->findOrFail($data['kartu_keluarga_id']);

            $isLegacyPayload = empty($data['nama_lengkap']) && empty($data['jenis_kelamin']);

            if ($user) {
                $penugasan = $this->activeAssignmentForUser($user);
                $this->assertKartuKeluargaScope($kartuKeluarga, $penugasan);
            }

            $ayah = $this->resolvePenduduk($data['ayah_id'] ?? null);
            $ibu = $this->resolvePenduduk($data['ibu_id'] ?? null);

            if ($isLegacyPayload) {
                $penduduk = $this->resolvePenduduk($data['penduduk_id'] ?? null);

                if (! $penduduk) {
                    throw ValidationException::withMessages(['penduduk_id' => 'Penduduk yang akan dicatat kelahirannya wajib dipilih.']);
                }

                $kelahiran = Kelahiran::create([
                    'penduduk_id' => $penduduk->id,
                    'kartu_keluarga_id' => $kartuKeluarga->id,
                    'ayah_id' => $ayah?->id,
                    'ibu_id' => $ibu?->id,
                    'nomor_akta_kelahiran' => $data['nomor_akta_kelahiran'] ?? null,
                    'tempat_lahir' => $data['tempat_lahir'] ?? $penduduk->tempat_lahir,
                    'tanggal_lahir' => $data['tanggal_lahir'] ?? $penduduk->tanggal_lahir,
                    'waktu_lahir' => $data['waktu_lahir'] ?? null,
                    'anak_ke' => $data['anak_ke'] ?? null,
                    'berat_lahir' => $data['berat_lahir'] ?? null,
                    'panjang_lahir' => $data['panjang_lahir'] ?? null,
                    'tanggal_akta_kelahiran' => $data['tanggal_akta_kelahiran'] ?? null,
                    'tanggal_dilaporkan' => $data['tanggal_dilaporkan'] ?? now()->toDateString(),
                    'catatan' => $data['catatan'] ?? null,
                    'dibuat_oleh' => $user?->id,
                ]);

                PeristiwaKependudukan::create([
                    'penduduk_id' => $penduduk->id,
                    'jenis' => 'kelahiran',
                    'tanggal_peristiwa' => $kelahiran->tanggal_lahir,
                    'nomor_referensi' => $kelahiran->nomor_akta_kelahiran,
                    'keterangan' => 'Pencatatan kelahiran.',
                    'dibuat_oleh' => $user?->id,
                ]);

                return $kelahiran;
            }

            $nik = $this->normalizeNik($data['nik'] ?? null);
            if ($nik !== null && Penduduk::query()->where('nik', $nik)->exists()) {
                throw ValidationException::withMessages(['nik' => 'NIK tersebut sudah terdaftar sebagai penduduk.']);
            }

            if (!empty($data['nomor_akta_kelahiran']) && Kelahiran::query()->where('nomor_akta_kelahiran', $data['nomor_akta_kelahiran'])->exists()) {
                throw ValidationException::withMessages(['nomor_akta_kelahiran' => 'Nomor akta kelahiran sudah terdaftar.']);
            }

            $pendudukBaru = Penduduk::create([
                'nik' => $nik,
                'nama_lengkap' => $data['nama_lengkap'],
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'golongan_darah' => $data['golongan_darah'] ?? null,
                'status_perkawinan' => $data['status_perkawinan'] ?? 'Belum Kawin',
                'kewarganegaraan' => $data['kewarganegaraan'] ?? 'WNI',
                'nama_ayah' => $ayah?->nama_lengkap,
                'nama_ibu' => $ibu?->nama_lengkap,
                'alamat' => $kartuKeluarga->alamat,
                'dusun_id' => $kartuKeluarga->dusun_id,
                'rw_id' => $kartuKeluarga->rw_id,
                'rt_id' => $kartuKeluarga->rt_id,
                'status_penduduk' => 'aktif',
                'masih_hidup' => true,
            ]);

            AnggotaKartuKeluarga::create([
                'kartu_keluarga_id' => $kartuKeluarga->id,
                'penduduk_id' => $pendudukBaru->id,
                'hubungan_keluarga' => 'Anak',
                'tanggal_masuk' => $pendudukBaru->tanggal_lahir,
                'aktif' => true,
            ]);

            $kelahiran = Kelahiran::create([
                'penduduk_id' => $pendudukBaru->id,
                'kartu_keluarga_id' => $kartuKeluarga->id,
                'ayah_id' => $ayah?->id,
                'ibu_id' => $ibu?->id,
                'nomor_akta_kelahiran' => $data['nomor_akta_kelahiran'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'],
                'tanggal_lahir' => $data['tanggal_lahir'],
                'waktu_lahir' => $data['waktu_lahir'] ?? null,
                'anak_ke' => $data['anak_ke'] ?? null,
                'berat_lahir' => $data['berat_lahir'] ?? null,
                'panjang_lahir' => $data['panjang_lahir'] ?? null,
                'tanggal_akta_kelahiran' => $data['tanggal_akta_kelahiran'] ?? null,
                'tanggal_dilaporkan' => $data['tanggal_dilaporkan'] ?? now()->toDateString(),
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $user?->id,
            ]);

            PeristiwaKependudukan::create([
                'penduduk_id' => $pendudukBaru->id,
                'jenis' => 'kelahiran',
                'tanggal_peristiwa' => $pendudukBaru->tanggal_lahir,
                'nomor_referensi' => $kelahiran->nomor_akta_kelahiran,
                'keterangan' => 'Pencatatan kelahiran bayi baru lahir.',
                'dibuat_oleh' => $user?->id,
            ]);

            return $kelahiran;
        });
    }

    private function assertActiveAssignment(?User $user): void
    {
        if (! $user) {
            return;
        }

        if (! $this->activeAssignmentForUser($user)) {
            throw ValidationException::withMessages([
                'kartu_keluarga_id' => 'User belum memiliki penugasan aktif untuk mencatat kelahiran.',
            ]);
        }
    }

    private function activeAssignmentForUser(?User $user): ?PenugasanPerangkat
    {
        if (! $user) {
            return null;
        }
        return PenugasanPerangkat::query()
            ->aktif()
            ->whereHas('perangkatDesa', fn ($query) => $query->where('user_id', $user->id))
            ->with(['jabatanDesa', 'desa', 'dusun'])
            ->first();
    }

    private function assertKartuKeluargaScope(KartuKeluarga $kartuKeluarga, PenugasanPerangkat $penugasan): void
    {
        if ($penugasan->jabatanDesa?->tingkat === 'dusun') {
            if ((int) $kartuKeluarga->desa_id !== (int) $penugasan->desa_id || (int) $kartuKeluarga->dusun_id !== (int) $penugasan->dusun_id) {
                throw ValidationException::withMessages([
                    'kartu_keluarga_id' => 'Kartu keluarga yang dipilih tidak sesuai dengan cakupan wilayah penugasan aktif Anda.',
                ]);
            }

            return;
        }

        if ((int) $kartuKeluarga->desa_id !== (int) $penugasan->desa_id) {
            throw ValidationException::withMessages([
                'kartu_keluarga_id' => 'Kartu keluarga yang dipilih tidak sesuai dengan cakupan wilayah penugasan aktif Anda.',
            ]);
        }
    }

    private function normalizeNik(?string $nik): ?string
    {
        if ($nik === null || trim($nik) === '') {
            return null;
        }

        return preg_replace('/\s+/', '', trim($nik));
    }

    private function resolvePenduduk(?int $pendudukId): ?Penduduk
    {
        if (! $pendudukId) {
            return null;
        }

        return Penduduk::query()->findOrFail($pendudukId);
    }
}
