<?php

namespace App\Services;

use App\Models\{Desa, Dusun, JabatanDesa, PenugasanPerangkat, PerangkatDesa, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenugasanPerangkatService
{
    public function create(array $data, ?User $actor = null): PenugasanPerangkat
    {
        return DB::transaction(function () use ($data, $actor) {
            $user = User::query()->lockForUpdate()->findOrFail($data['user_id']);
            $jabatan = JabatanDesa::query()->where('aktif', true)->lockForUpdate()->findOrFail($data['jabatan_desa_id']);
            $this->validateScope($data, $jabatan);
            if (PenugasanPerangkat::aktif()->whereHas('perangkatDesa', fn ($q) => $q->where('user_id', $user->id))->exists()) {
                throw ValidationException::withMessages(['user_id' => 'Pengguna ini masih memiliki penugasan aktif. Akhiri atau ganti penugasan sebelumnya terlebih dahulu.']);
            }
            $this->assertPositionAvailable($data, $jabatan);
            $perangkat = PerangkatDesa::withTrashed()->firstOrCreate(['user_id' => $user->id], ['nama_lengkap' => $user->name, 'aktif' => true]);
            if ($perangkat->trashed()) $perangkat->restore();
            $assignment = $perangkat->penugasan()->create(array_merge($data, ['dibuat_oleh' => $actor?->id, 'status' => 'aktif']));
            $this->syncRole($user, $jabatan);
            return $assignment;
        });
    }

    public function replace(PenugasanPerangkat $old, array $data, ?User $actor = null): PenugasanPerangkat
    {
        return DB::transaction(function () use ($old, $data, $actor) {
            $old = PenugasanPerangkat::query()->lockForUpdate()->findOrFail($old->id);
            if ($old->status !== 'aktif') throw ValidationException::withMessages(['penugasan' => 'Penugasan ini sudah tidak aktif.']);
            $end = $data['tanggal_selesai_lama'];
            if ($data['tanggal_mulai'] <= $end) throw ValidationException::withMessages(['tanggal_mulai' => 'Tanggal mulai pejabat baru harus setelah tanggal selesai pejabat lama.']);
            $old->update(['status' => 'selesai', 'tanggal_selesai' => $end, 'nomor_sk_pemberhentian' => $data['nomor_sk_pemberhentian'] ?? null, 'tanggal_sk_pemberhentian' => $data['tanggal_sk_pemberhentian'] ?? null, 'catatan' => $data['catatan'] ?? $old->catatan]);
            $new = $this->create(['user_id' => $data['user_id'], 'jabatan_desa_id' => $old->jabatan_desa_id, 'desa_id' => $old->desa_id, 'dusun_id' => $old->dusun_id, 'tanggal_mulai' => $data['tanggal_mulai'], 'tanggal_selesai' => $data['tanggal_selesai'] ?? null, 'nomor_sk_pengangkatan' => $data['nomor_sk_pengangkatan'] ?? null, 'tanggal_sk_pengangkatan' => $data['tanggal_sk_pengangkatan'] ?? null, 'catatan' => $data['catatan'] ?? null], $actor);
            $this->releaseUnusedRole($old->perangkatDesa->user, $old->jabatanDesa);
            return $new;
        });
    }

    public function end(PenugasanPerangkat $assignment, array $data): PenugasanPerangkat
    {
        return DB::transaction(function () use ($assignment, $data) {
            $assignment = PenugasanPerangkat::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($assignment->status !== 'aktif') throw ValidationException::withMessages(['penugasan' => 'Penugasan ini sudah tidak aktif.']);
            $assignment->update(array_merge($data, ['status' => 'selesai']));
            $this->releaseUnusedRole($assignment->perangkatDesa->user, $assignment->jabatanDesa);
            return $assignment;
        });
    }

    private function validateScope(array $data, JabatanDesa $jabatan): void
    {
        $desa = Desa::query()->where('aktif', true)->find($data['desa_id']);
        if (!$desa) throw ValidationException::withMessages(['desa_id' => 'Desa yang dipilih tidak aktif atau tidak ditemukan.']);
        if ($jabatan->tingkat === 'dusun') {
            $dusun = Dusun::query()->where('aktif', true)->whereKey($data['dusun_id'] ?? null)->first();
            if (!$dusun || $dusun->desa_id != $desa->id) throw ValidationException::withMessages(['dusun_id' => 'Dusun wajib dipilih dan harus berada di desa yang dipilih.']);
        } elseif (!empty($data['dusun_id'])) throw ValidationException::withMessages(['dusun_id' => 'Jabatan tingkat desa tidak boleh memiliki dusun.']);
    }

    private function assertPositionAvailable(array $data, JabatanDesa $jabatan): void
    {
        $query = PenugasanPerangkat::aktif()->where('jabatan_desa_id', $jabatan->id)->where('desa_id', $data['desa_id']);
        $jabatan->tingkat === 'dusun' ? $query->where('dusun_id', $data['dusun_id']) : $query->whereNull('dusun_id');
        if ($query->exists()) throw ValidationException::withMessages(['jabatan_desa_id' => 'Jabatan dan wilayah tersebut masih memiliki pejabat aktif. Gunakan proses Ganti Pejabat.']);
    }

    private function syncRole(User $user, JabatanDesa $jabatan): void
    {
        $role = ['JBD-0001' => ['kepala_desa', 'kepala-desa'], 'JBD-0002' => ['sekdes'], 'JBD-0003' => ['kepala_dusun', 'kepala-dusun']][$jabatan->kode] ?? [];
        foreach ($role as $candidate) if (\Spatie\Permission\Models\Role::where('name', $candidate)->exists()) { $user->assignRole($candidate); return; }
    }

    private function releaseUnusedRole(User $user, JabatanDesa $jabatan): void
    {
        if (!PenugasanPerangkat::aktif()->whereHas('perangkatDesa', fn ($q) => $q->where('user_id', $user->id))->whereHas('jabatanDesa', fn ($q) => $q->where('id', $jabatan->id))->exists()) {
            $role = ['JBD-0001' => 'kepala_desa', 'JBD-0002' => 'sekdes', 'JBD-0003' => 'kepala_dusun'][$jabatan->kode] ?? null;
            if ($role) {
                $user->removeRole($role);
                if ($jabatan->kode === 'JBD-0001') $user->removeRole('kepala-desa');
                if ($jabatan->kode === 'JBD-0003') $user->removeRole('kepala-dusun');
            }
        }
    }
}
