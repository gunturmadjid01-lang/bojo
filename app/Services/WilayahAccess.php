<?php

namespace App\Services;

use App\Models\{Desa, Dusun, KartuKeluarga, Penduduk, PenugasanPerangkat, RT, RW, User};
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class WilayahAccess
{
    private ?User $actor = null;
    private bool $explicitActor = false;
    private bool $system = false;

    // Explicit maintenance/seeding context. Never use this for user requests or exports.
    public function forSystem(Closure $callback): mixed
    {
        $previous = $this->system;
        $this->system = true;
        try { return $callback(); }
        finally { $this->system = $previous; }
    }

    // Jobs must wrap their entire operation in forUser(), including queries.
    public function forUser(User $user, Closure $callback): mixed
    {
        [$previous, $explicit] = [$this->actor, $this->explicitActor];
        $this->actor = $user;
        $this->explicitActor = true;
        try { return $callback(); }
        finally { [$this->actor, $this->explicitActor] = [$previous, $explicit]; }
    }

    public function user(): ?User { return $this->explicitActor ? $this->actor : Auth::user(); }

    public function unrestricted(): bool
    {
        return $this->system || ($this->user()?->isSuperAdmin() ?? false);
    }

    public function assignments()
    {
        $user = $this->user();
        if (!$user) return collect();
        // Cache only on this authenticated model; a new HTTP request loads a new user.
        return $user->loadMissing('perangkatDesa.penugasanAktif.jabatanDesa')
            ->perangkatDesa?->penugasanAktif ?? collect();
    }

    public function apply(Builder $query): void
    {
        if ($this->unrestricted()) return;
        $model = $query->getModel();
        $table = $model->getTable();
        $assignments = $this->assignments();
        if ($assignments->isEmpty()) { $query->whereRaw('1 = 0'); return; }
        $query->where(function (Builder $scope) use ($assignments, $table) {
            foreach ($assignments as $assignment) {
                $level = $assignment->jabatanDesa?->tingkat;
                if (!in_array($level, ['desa', 'dusun']) || ($level === 'dusun' && !$assignment->dusun_id)) continue;
                $scope->orWhere(function (Builder $branch) use ($assignment, $level, $table) {
                    $dusuns = Dusun::withoutGlobalScope('wilayah')->select('id')->where('desa_id', $assignment->desa_id);
                    if ($level === 'dusun') $dusuns->whereKey($assignment->dusun_id);
                    $citizens = Penduduk::withoutGlobalScope('wilayah')->select('id')->whereIn('dusun_id', clone $dusuns);
                    $families = KartuKeluarga::withoutGlobalScope('wilayah')->select('id')->where('desa_id', $assignment->desa_id);
                    if ($level === 'dusun') $families->whereIn('dusun_id', clone $dusuns);
                    match ($table) {
                        'desas' => $branch->where('desas.id', $assignment->desa_id),
                        'dusuns' => $branch->whereIn('dusuns.id', $dusuns),
                        'penduduks', 'r_w_s' => $branch->whereIn($table.'.dusun_id', $dusuns),
                        'kartu_keluargas' => $branch->whereIn($table.'.id', $families),
                        'penugasan_perangkats' => $branch->where($table.'.desa_id', $assignment->desa_id)->when($level === 'dusun', fn ($q) => $q->whereIn($table.'.dusun_id', $dusuns)),
                        'r_t_s' => $branch->whereIn('r_t_s.rw_id', RW::withoutGlobalScope('wilayah')->select('id')->whereIn('dusun_id', $dusuns)),
                        'anggota_kartu_keluargas' => $branch->whereIn($table.'.kartu_keluarga_id', $families)->whereIn($table.'.penduduk_id', $citizens),
                        'kelahirans', 'sosial_ekonomi_keluargas' => $branch->whereIn($table.'.kartu_keluarga_id', $families),
                        'kedatangan_penduduks' => $branch->whereIn($table.'.kartu_keluarga_tujuan_id', $families),
                        'perpindahan_penduduks' => $branch->whereHas('anggotaPerpindahan.penduduk', fn ($q) => $q->whereIn('penduduks.id', $citizens)),
                        'penerima_bantuans', 'dokumen_penduduks' => $branch->where(fn ($q) => $q->whereIn($table.'.penduduk_id', $citizens)->orWhereIn($table.'.kartu_keluarga_id', $families)),
                        'penyaluran_bantuans' => $branch->whereHas('penerima'),
                        default => $branch->whereIn($table.'.penduduk_id', $citizens),
                    };
                });
            }
            // Invalid assignments must never leave an empty, unrestricted group.
            $scope->orWhereRaw('1 = 0');
        });
    }

    public function validateWrite(Model $model): void
    {
        if ($this->unrestricted()) return;
        if ($this->assignments()->isEmpty()) abort(403, 'Tidak ada penugasan wilayah aktif.');
        if ($model->exists && !$model->newQuery()->whereKey($model->getKey())->exists()) abort(403);
        foreach ($model->getAttributes() as $field => $value) {
            $class = self::references()[$field] ?? null;
            if ($class && $value !== null && !$class::query()->whereKey($value)->exists()) {
                throw ValidationException::withMessages([$field => 'Pilihan berada di luar wilayah penugasan Anda.']);
            }
        }
        if ($model instanceof Penduduk || $model instanceof RW) {
            if (!$model->dusun_id) throw ValidationException::withMessages(['dusun_id' => 'Dusun dalam wilayah penugasan wajib dipilih.']);
        }
        if ($model instanceof KartuKeluarga && !$model->dusun_id && $this->assignments()->every(fn ($a) => $a->jabatanDesa?->tingkat === 'dusun')) {
            throw ValidationException::withMessages(['dusun_id' => 'Dusun dalam wilayah penugasan wajib dipilih.']);
        }
        $dusun = $model->dusun_id ? Dusun::find($model->dusun_id) : null;
        $rw = $model->rw_id ? RW::find($model->rw_id) : null;
        $rt = $model->rt_id ? RT::find($model->rt_id) : null;
        if ($dusun && $model->desa_id && $dusun->desa_id != $model->desa_id) $this->invalid('dusun_id');
        if ($rw && $model->dusun_id && $rw->dusun_id != $model->dusun_id) $this->invalid('rw_id');
        if ($rt && $model->rw_id && $rt->rw_id != $model->rw_id) $this->invalid('rt_id');
    }

    private function invalid(string $field): never
    {
        throw ValidationException::withMessages([$field => 'Susunan desa, dusun, RW, dan RT tidak sesuai.']);
    }

    public static function references(): array
    {
        return ['desa_id' => Desa::class, 'dusun_id' => Dusun::class, 'rw_id' => RW::class, 'rt_id' => RT::class,
            'penduduk_id' => Penduduk::class, 'ayah_id' => Penduduk::class, 'ibu_id' => Penduduk::class, 'pasangan_id' => Penduduk::class,
            'kartu_keluarga_id' => KartuKeluarga::class, 'kartu_keluarga_tujuan_id' => KartuKeluarga::class,
            'penerima_bantuan_id' => \App\Models\PenerimaBantuan::class, 'kedatangan_penduduk_id' => \App\Models\KedatanganPenduduk::class];
    }
}
