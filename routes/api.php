<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PendudukController;
use App\Http\Controllers\Api\WilayahController;
use App\Http\Controllers\Api\PopulationOptionsController;
use App\Http\Controllers\Api\PopulationController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\AccessController;
use App\Http\Controllers\Api\PerangkatDesaController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
    Route::middleware(['auth:sanctum', 'throttle:api', 'offline.idempotency'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/wilayah', [WilayahController::class, 'index']);
        Route::get('/kependudukan/options', PopulationOptionsController::class);
        Route::get('/access/users', [AccessController::class, 'users']);
        Route::get('/access/roles', [AccessController::class, 'roles']);
        Route::post('/access/users', [AccessController::class, 'storeUser']);
        Route::put('/access/users/{id}', [AccessController::class, 'updateUser'])->whereNumber('id');
        Route::delete('/access/users/{id}', [AccessController::class, 'destroyUser'])->whereNumber('id');
        Route::get('/access/permissions', [AccessController::class, 'matrix']);
        Route::put('/access/permissions', [AccessController::class, 'updateMatrix']);
        Route::get('/penduduk', [PendudukController::class, 'index']);
        Route::get('/penduduk/{penduduk}', [PendudukController::class, 'show']);
        Route::post('/penduduk', [PopulationController::class, 'store'])->defaults('module', 'penduduk');
        Route::put('/penduduk/{id}', [PopulationController::class, 'update'])->whereNumber('id')->defaults('module', 'penduduk');
        Route::patch('/penduduk/{id}', [PopulationController::class, 'update'])->whereNumber('id')->defaults('module', 'penduduk');
        Route::delete('/penduduk/{id}', [PopulationController::class, 'destroy'])->whereNumber('id')->defaults('module', 'penduduk');
        $populationModules = ['kartu-keluarga', 'anggota-kartu-keluarga', 'kelahiran', 'kematian', 'penduduk-datang', 'penduduk-pindah'];
        foreach ($populationModules as $module) {
            Route::get("/{$module}", [PopulationController::class, 'index'])->defaults('module', $module);
            Route::post("/{$module}", [PopulationController::class, 'store'])->defaults('module', $module);
            Route::get("/{$module}/{id}", [PopulationController::class, 'show'])->whereNumber('id')->defaults('module', $module);
            Route::match(['put', 'patch', 'post'], "/{$module}/{id}", [PopulationController::class, 'update'])->whereNumber('id')->defaults('module', $module);
            Route::delete("/{$module}/{id}", [PopulationController::class, 'destroy'])->whereNumber('id')->defaults('module', $module);
        }

        Route::get('/jabatan-desa', [PerangkatDesaController::class, 'jabatanIndex']);
        Route::post('/jabatan-desa', [PerangkatDesaController::class, 'jabatanStore']);
        Route::get('/jabatan-desa/{jabatanDesa}', [PerangkatDesaController::class, 'jabatanShow']);
        Route::match(['put', 'patch', 'post'], '/jabatan-desa/{jabatanDesa}', [PerangkatDesaController::class, 'jabatanUpdate']);
        Route::delete('/jabatan-desa/{jabatanDesa}', [PerangkatDesaController::class, 'jabatanDestroy']);
        Route::get('/penugasan-perangkat/options', [PerangkatDesaController::class, 'options']);
        Route::get('/penugasan-perangkat/history', [PerangkatDesaController::class, 'history']);
        Route::get('/penugasan-perangkat', [PerangkatDesaController::class, 'assignments']);
        Route::post('/penugasan-perangkat', [PerangkatDesaController::class, 'assignmentStore']);
        Route::get('/penugasan-perangkat/{penugasanPerangkat}', [PerangkatDesaController::class, 'assignmentShow']);
        Route::post('/penugasan-perangkat/{penugasanPerangkat}/replace', [PerangkatDesaController::class, 'replace']);
        Route::post('/penugasan-perangkat/{penugasanPerangkat}/end', [PerangkatDesaController::class, 'end']);

        $resources = [
            'desa', 'dusun',
            'pendidikan', 'pekerjaan',
        ];
        Route::get('/{resource}', [ResourceController::class, 'index'])->whereIn('resource', $resources);
        Route::post('/{resource}', [ResourceController::class, 'store'])->whereIn('resource', $resources);
        Route::post('/{resource}/{id}', [ResourceController::class, 'update'])->whereIn('resource', $resources)->whereNumber('id');
        Route::get('/{resource}/{id}', [ResourceController::class, 'show'])->whereIn('resource', $resources)->whereNumber('id');
        Route::put('/{resource}/{id}', [ResourceController::class, 'update'])->whereIn('resource', $resources)->whereNumber('id');
        Route::patch('/{resource}/{id}', [ResourceController::class, 'update'])->whereIn('resource', $resources)->whereNumber('id');
        Route::delete('/{resource}/{id}', [ResourceController::class, 'destroy'])->whereIn('resource', $resources)->whereNumber('id');
    });
});
