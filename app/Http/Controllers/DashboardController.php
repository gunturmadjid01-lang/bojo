<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function reports(Request $request)
    {
        $service = app(\App\Services\StatistikKependudukanService::class);
        $modules = ['penduduk' => 'Penduduk', 'kartu-keluarga' => 'Kartu Keluarga'];
        $year = $request->integer('statistik_tahun', now()->year);
        $moduleStatistics = collect($modules)->mapWithKeys(fn ($title, $module) => [$module => ['title' => $title, 'statistics' => $service->for($module, $request->user(), $year)]])->all();
        return \Inertia\Inertia::render('reports/index', ['moduleStatistics' => $moduleStatistics, 'statisticYear' => $year, 'statisticYears' => $service->years()]);
    }

    public function index(Request $request)
    {
        $service = app(\App\Services\StatistikKependudukanService::class);
        $modules = [
            'kartu-keluarga' => 'Kartu Keluarga',
            'penduduk' => 'Penduduk',
            'kelahiran' => 'Kelahiran',
            'kematian' => 'Kematian',
            'penduduk-datang' => 'Penduduk Masuk',
            'penduduk-pindah' => 'Penduduk Keluar',
        ];
        $year = $request->integer('statistik_tahun', now()->year);
        $canViewResidents = \App\Support\ModulePermissions::allows($request->user(), 'penduduk', 'view');
        $residents = $canViewResidents ? \App\Models\Penduduk::with(['dusun:id,nama', 'pendidikan:id,nama'])->latest()->limit(100)->get()->map(fn ($p) => [
            'id' => $p->id, 'nik' => $p->nik, 'nama' => $p->nama_lengkap,
            'tanggalLahir' => $p->tanggal_lahir?->format('d-m-Y'), 'jenisKelamin' => $p->jenis_kelamin,
            'pendidikan' => $p->pendidikan?->nama, 'dusun' => $p->dusun?->nama,
            'statusTinggal' => $p->status_penduduk,
        ]) : collect();
        $moduleStatistics = collect($modules)->filter(fn ($title, $module) => \App\Support\ModulePermissions::allows($request->user(), $module, 'view'))->mapWithKeys(fn ($title, $module) => [$module => ['title' => $title, 'statistics' => $service->for($module, $request->user(), $year)]])->all();
        return \Inertia\Inertia::render('dashboard', [
            'residents' => $residents,
            'jumlahPenduduk' => $canViewResidents ? \App\Models\Penduduk::count() : 0,
            'moduleStatistics' => $moduleStatistics,
            'statisticYear' => $year,
            'statisticYears' => $service->years(),
        ]);
    }
}
