<?php

namespace App\Support;

use App\Models\User;

class ModulePermissions
{
    public const MODULES = [
        'desa' => ['desas', 'Desa'], 'dusun' => ['dusuns', 'Dusun'],
        'penduduk' => ['penduduks', 'Penduduk'], 'kartu-keluarga' => ['kartu_keluargas', 'Kartu Keluarga'],
        'anggota-kartu-keluarga' => ['anggota_kartu_keluargas', 'Anggota Kartu Keluarga'],
        'kelahiran' => ['kelahirans', 'Kelahiran'], 'kematian' => ['kematians', 'Kematian'],
        'penduduk-datang' => ['penduduk_datangs', 'Penduduk Datang'], 'penduduk-pindah' => ['penduduk_pindahs', 'Penduduk Pindah'],
        'sosial-ekonomi-keluarga' => ['sosial_ekonomi_keluargas', 'Sosial Ekonomi Keluarga'],
        'sosial-ekonomi-penduduk' => ['sosial_ekonomi_penduduks', 'Sosial Ekonomi Penduduk'],
        'disabilitas-penduduk' => ['disabilitas_penduduks', 'Disabilitas Penduduk'],
        'penyaluran-bantuan' => ['penyaluran_bantuans', 'Penyaluran Bantuan'],
        'penerima-bantuan' => ['penerima_bantuans', 'Penerima Bantuan'],
        'program-bantuan' => ['program_bantuans', 'Program Bantuan'],
        'pendidikan' => ['pendidikans', 'Pendidikan'], 'pekerjaan' => ['pekerjaans', 'Pekerjaan'],
        'disabilitas' => ['disabilitas', 'Jenis Disabilitas'], 'jabatan-desa' => ['jabatan_desas', 'Jabatan Desa'],
        'users' => ['users', 'Users'], 'permissions' => ['permissions', 'Permissions'],
        'reports' => ['reports', 'Laporan & Statistik'],
        'penugasan-perangkat' => ['penugasan_perangkats', 'Penugasan Perangkat'],
    ];

    public const GROUPS = [
        'Master Data' => ['Desa', 'Dusun', 'Pendidikan', 'Pekerjaan'],
        'Kependudukan' => ['Kartu Keluarga', 'Anggota Kartu Keluarga', 'Penduduk', 'Kelahiran', 'Kematian', 'Penduduk Datang', 'Penduduk Pindah'],
        'Data Sosial' => ['Sosial Ekonomi Keluarga', 'Sosial Ekonomi Penduduk', 'Jenis Disabilitas', 'Disabilitas Penduduk'],
        'Bantuan Sosial' => ['Program Bantuan', 'Penerima Bantuan', 'Penyaluran Bantuan'],
        'Perangkat Desa' => ['Jabatan Desa', 'Penugasan Perangkat'],
        'Pengaturan Sistem' => ['Users', 'Permissions'],
        'Laporan & Statistik' => ['Reports'],
    ];

    public static function allows(?User $user, string $module, string $action): bool
    {
        if (!$user) return false;
        if ($user->roles->contains(fn ($role): bool => in_array(
            strtolower(str_replace([' ', '-'], '_', $role->name)),
            ['super_admin', 'superadmin'],
            true,
        ))) return true;
        if (!isset(self::MODULES[$module])) return false;
        $permissions = self::permissionNames($module, $action);

        return $user->getAllPermissions()->pluck('name')->map('strtolower')->intersect($permissions)->isNotEmpty();
    }

    public static function permissionNames(string $module, string $action): \Illuminate\Support\Collection
    {
        if (!isset(self::MODULES[$module])) return collect();

        [$table, $label] = self::MODULES[$module];

        return collect([$module, $table, $label])
            ->map(fn (string $value): string => strtolower(trim($value)))
            ->flatMap(fn (string $value): array => [$value, str_replace(['_', '-'], ' ', $value)])
            ->unique()
            ->map(fn (string $alias): string => strtolower("$action $alias"))
            ->values();
    }

    public static function moduleForLabel(string $label): ?string
    {
        foreach (self::MODULES as $module => [, $moduleLabel]) {
            if (strcasecmp($moduleLabel, $label) === 0) return $module;
        }

        return null;
    }

    public static function managedPermissionNames(): \Illuminate\Support\Collection
    {
        return collect(array_keys(self::MODULES))
            ->flatMap(fn (string $module) => collect(['view', 'create', 'edit', 'delete'])
                ->flatMap(fn (string $action) => self::permissionNames($module, $action)))
            ->unique()
            ->values();
    }

    public static function hasAnyPermission(?User $user, array $permissions): bool
    {
        if (!$user) return false;

        if ($user->roles->contains(fn ($role): bool => in_array(
            strtolower(str_replace([' ', '-'], '_', $role->name)),
            ['super_admin', 'superadmin'],
            true,
        ))) return true;

        $ownedPermissions = $user->getAllPermissions()->pluck('name')->map('strtolower');

        return collect($permissions)
            ->map(fn (string $permission): string => strtolower($permission))
            ->contains(fn (string $permission): bool => $ownedPermissions->contains($permission));
    }
}
