<?php

namespace App\Services;

use App\Models\AnggotaKartuKeluarga;
use App\Models\KartuKeluarga;
use App\Models\Kematian;
use App\Models\Penduduk;
use App\Models\PenugasanPerangkat;
use App\Models\PeristiwaKependudukan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KematianService
{
    public function store(array $data, ?User $actor = null): Kematian
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = $actor ?? auth()->user();
            $this->assertActiveAssignment($user);

            $penduduk = Penduduk::query()->lockForUpdate()->find($data['penduduk_id'] ?? null);
            if (! $penduduk) {
                throw ValidationException::withMessages(['penduduk_id' => 'Penduduk yang dipilih tidak ditemukan.']);
            }

            $this->assertPendudukScope($penduduk, $user);
            $this->assertPendudukIsAlive($penduduk);
            $this->assertNoExistingDeathRecord($penduduk);
            $this->assertDeathDateIsValid($penduduk, $data);

            if (! empty($data['tanggal_dilaporkan']) && $data['tanggal_dilaporkan'] < $data['tanggal_meninggal']) {
                throw ValidationException::withMessages([
                    'tanggal_dilaporkan' => 'Tanggal dilaporkan tidak boleh sebelum tanggal meninggal.',
                ]);
            }

            $kartuKeluarga = $this->activeKartuKeluargaForPenduduk($penduduk);

            $kematian = Kematian::create([
                'penduduk_id' => $penduduk->id,
                'tanggal_meninggal' => $data['tanggal_meninggal'],
                'waktu_meninggal' => $data['waktu_meninggal'] ?? null,
                'tempat_meninggal' => $data['tempat_meninggal'] ?? null,
                'penyebab_meninggal' => $data['penyebab_meninggal'] ?? null,
                'nomor_surat_kematian' => $data['nomor_surat_kematian'] ?? null,
                'tanggal_dilaporkan' => $data['tanggal_dilaporkan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $user?->id,
            ]);

            $penduduk->update([
                'status_penduduk' => 'meninggal',
                'masih_hidup' => false,
            ]);

            AnggotaKartuKeluarga::query()
                ->where('penduduk_id', $penduduk->id)
                ->where('aktif', true)
                ->update([
                    'aktif' => false,
                    'tanggal_keluar' => $data['tanggal_meninggal'],
                    'alasan_keluar' => 'meninggal',
                ]);

            PeristiwaKependudukan::create([
                'penduduk_id' => $penduduk->id,
                'jenis' => 'kematian',
                'tanggal_peristiwa' => $data['tanggal_meninggal'],
                'nomor_referensi' => $data['nomor_surat_kematian'] ?? null,
                'keterangan' => 'Pencatatan kematian penduduk.',
                'dibuat_oleh' => $user?->id,
            ]);

            if ($this->isKepalaKeluarga($penduduk)) {
                $kartuKeluarga?->update(['perlu_kepala_keluarga_baru' => true]);
            }

            return $kematian;
        });
    }

    public function update(Kematian $kematian, array $data, ?User $actor = null): Kematian
    {
        return DB::transaction(function () use ($kematian, $data, $actor) {
            $user = $actor ?? auth()->user();
            $this->assertActiveAssignment($user);

            $penduduk = $kematian->penduduk()->lockForUpdate()->first();
            if (! $penduduk) {
                throw ValidationException::withMessages(['penduduk_id' => 'Penduduk yang meninggal tidak ditemukan.']);
            }

            $this->assertPendudukScope($penduduk, $user);
            $this->assertDeathDateIsValid($penduduk, $data);

            if (! empty($data['tanggal_dilaporkan']) && $data['tanggal_dilaporkan'] < $data['tanggal_meninggal']) {
                throw ValidationException::withMessages([
                    'tanggal_dilaporkan' => 'Tanggal dilaporkan tidak boleh sebelum tanggal meninggal.',
                ]);
            }

            $kematian->update([
                'tanggal_meninggal' => $data['tanggal_meninggal'],
                'waktu_meninggal' => $data['waktu_meninggal'] ?? null,
                'tempat_meninggal' => $data['tempat_meninggal'] ?? null,
                'penyebab_meninggal' => $data['penyebab_meninggal'] ?? null,
                'nomor_surat_kematian' => $data['nomor_surat_kematian'] ?? null,
                'tanggal_dilaporkan' => $data['tanggal_dilaporkan'] ?? null,
                'catatan' => $data['catatan'] ?? null,
                'dibuat_oleh' => $user?->id ?? $kematian->dibuat_oleh,
            ]);

            AnggotaKartuKeluarga::query()
                ->where('penduduk_id', $kematian->penduduk_id)
                ->where('aktif', false)
                ->where('alasan_keluar', 'meninggal')
                ->update([
                    'tanggal_keluar' => $data['tanggal_meninggal'],
                ]);

            PeristiwaKependudukan::query()
                ->where('penduduk_id', $kematian->penduduk_id)
                ->where('jenis', 'kematian')
                ->update([
                    'tanggal_peristiwa' => $data['tanggal_meninggal'],
                    'nomor_referensi' => $data['nomor_surat_kematian'] ?? null,
                ]);

            return $kematian->fresh();
        });
    }

    private function assertActiveAssignment(?User $user): void
    {
        if (! $user) {
            return;
        }

        if (! $this->activeAssignmentForUser($user)) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'User belum memiliki penugasan aktif untuk mencatat kematian.',
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

    private function assertPendudukScope(Penduduk $penduduk, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $assignment = $this->activeAssignmentForUser($user);
        if (! $assignment) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'User belum memiliki penugasan aktif untuk mencatat kematian.',
            ]);
        }

        $pendudukDusun = $penduduk->dusun;
        $desaId = $pendudukDusun?->desa_id ?? null;

        if ($assignment->jabatanDesa?->tingkat === 'dusun') {
            if ($penduduk->dusun_id !== $assignment->dusun_id) {
                throw ValidationException::withMessages([
                    'penduduk_id' => 'Penduduk yang dipilih tidak berada dalam cakupan wilayah penugasan aktif Anda.',
                ]);
            }

            return;
        }

        if ($penduduk->dusun_id && $desaId && (int) $desaId !== (int) $assignment->desa_id) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Penduduk yang dipilih tidak berada dalam desa penugasan aktif Anda.',
            ]);
        }
    }

    private function assertPendudukIsAlive(Penduduk $penduduk): void
    {
        if ($penduduk->status_penduduk === 'meninggal' || ! $penduduk->masih_hidup) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Penduduk tersebut sudah tercatat meninggal.',
            ]);
        }
    }

    private function assertNoExistingDeathRecord(Penduduk $penduduk): void
    {
        if (Kematian::query()->where('penduduk_id', $penduduk->id)->exists()) {
            throw ValidationException::withMessages([
                'penduduk_id' => 'Penduduk tersebut sudah mempunyai data kematian yang tercatat.',
            ]);
        }
    }

    private function assertDeathDateIsValid(Penduduk $penduduk, array $data): void
    {
        $tanggalMeninggal = $data['tanggal_meninggal'] ?? null;
        if (! $tanggalMeninggal) {
            throw ValidationException::withMessages([
                'tanggal_meninggal' => 'Tanggal meninggal wajib diisi.',
            ]);
        }

        if ($penduduk->tanggal_lahir && $tanggalMeninggal < $penduduk->tanggal_lahir->format('Y-m-d')) {
            throw ValidationException::withMessages([
                'tanggal_meninggal' => 'Tanggal meninggal tidak boleh lebih kecil dari tanggal lahir penduduk.',
            ]);
        }

        if ($tanggalMeninggal > now()->toDateString()) {
            throw ValidationException::withMessages([
                'tanggal_meninggal' => 'Tanggal meninggal tidak boleh lebih besar dari tanggal hari ini.',
            ]);
        }
    }

    private function activeKartuKeluargaForPenduduk(Penduduk $penduduk): ?KartuKeluarga
    {
        $anggota = AnggotaKartuKeluarga::query()
            ->where('penduduk_id', $penduduk->id)
            ->where('aktif', true)
            ->latest('id')
            ->first();

        if (! $anggota) {
            return null;
        }

        return $anggota->kartuKeluarga;
    }

    private function isKepalaKeluarga(Penduduk $penduduk): bool
    {
        return AnggotaKartuKeluarga::query()
            ->where('penduduk_id', $penduduk->id)
            ->where('aktif', true)
            ->where('hubungan_keluarga', 'Kepala Keluarga')
            ->exists();
    }
}
