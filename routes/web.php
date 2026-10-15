<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Settings\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings/password', [\App\Http\Controllers\Settings\PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/settings/password', [\App\Http\Controllers\Settings\PasswordController::class, 'update'])->name('password.update');
});

Route::middleware(['auth', \App\Http\Middleware\AuthorizeModule::class])->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/laporan', [\App\Http\Controllers\DashboardController::class, 'reports'])->name('reports.index');

    Route::resource('desa', \App\Http\Controllers\DesaController::class);
    Route::resource('dusun', \App\Http\Controllers\DusunController::class);
    Route::resource('kartu-keluarga', \App\Http\Controllers\KartuKeluargaController::class);
    Route::resource('anggota-kartu-keluarga', \App\Http\Controllers\AnggotaKartuKeluargaController::class);
    Route::resource('penduduk', \App\Http\Controllers\PendudukController::class);
    Route::resource('kelahiran', \App\Http\Controllers\KelahiranController::class);
    Route::resource('kematian', \App\Http\Controllers\KematianController::class);
    Route::resource('penduduk-datang', \App\Http\Controllers\PendudukDatangController::class);
    Route::resource('penduduk-pindah', \App\Http\Controllers\PendudukPindahController::class);
    Route::get('/perpindahan-penduduk/{perpindahanPenduduk}', [\App\Http\Controllers\PendudukPindahController::class, 'showPerpindahan'])->name('penduduk-pindah.header.show');
    Route::resource('pendidikan', \App\Http\Controllers\PendidikanController::class);
    Route::resource('pekerjaan', \App\Http\Controllers\PekerjaanController::class);
    Route::resource('jabatan-desa', \App\Http\Controllers\JabatanDesaController::class);
    Route::resource('users', UserController::class)->except(['index']);
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/permissions', [\App\Http\Controllers\PermissionMatrixController::class, 'index'])->name('permissions.index');
    Route::post('/permissions', [\App\Http\Controllers\PermissionMatrixController::class, 'store'])->name('permissions.store');
    Route::get('/penugasan-perangkat', [\App\Http\Controllers\PenugasanPerangkatController::class, 'index'])->name('penugasan-perangkat.index');
    Route::get('/penugasan-perangkat/create', [\App\Http\Controllers\PenugasanPerangkatController::class, 'create'])->name('penugasan-perangkat.create');
    Route::post('/penugasan-perangkat', [\App\Http\Controllers\PenugasanPerangkatController::class, 'store'])->name('penugasan-perangkat.store');
    Route::get('/penugasan-perangkat/riwayat', [\App\Http\Controllers\PenugasanPerangkatController::class, 'history'])->name('penugasan-perangkat.history');
    Route::get('/penugasan-perangkat/{penugasanPerangkat}/ganti', [\App\Http\Controllers\PenugasanPerangkatController::class, 'replaceForm'])->name('penugasan-perangkat.replace.form');
    Route::get('/penugasan-perangkat/{penugasanPerangkat}/akhiri', [\App\Http\Controllers\PenugasanPerangkatController::class, 'endForm'])->name('penugasan-perangkat.end.form');
    Route::get('/penugasan-perangkat/{penugasanPerangkat}', [\App\Http\Controllers\PenugasanPerangkatController::class, 'show'])->name('penugasan-perangkat.show');
    Route::post('/penugasan-perangkat/{penugasanPerangkat}/ganti', [\App\Http\Controllers\PenugasanPerangkatController::class, 'replace'])->name('penugasan-perangkat.replace');
    Route::post('/penugasan-perangkat/{penugasanPerangkat}/akhiri', [\App\Http\Controllers\PenugasanPerangkatController::class, 'end'])->name('penugasan-perangkat.end');
});
