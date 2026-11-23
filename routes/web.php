<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KataSandiController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Publik: menu peta
Route::controller(PetaController::class)->group(function () {
    Route::get('/', 'index')->name('peta.index');
    Route::get('/pencarian', 'cari')->name('peta.cari');
    Route::get('/klaster', 'klaster')->name('peta.klaster');
    Route::get('/peta-panas', 'panas')->name('peta.panas');
    Route::get('/wilayah', 'wilayah')->name('peta.wilayah');
    Route::get('/rute', 'rute')->name('peta.rute');
    Route::get('/cek-alamat', 'alamat')->name('peta.alamat');
    Route::get('/data/sekolah', 'geojson')->name('peta.data');
    Route::get('/data/wilayah', 'geojsonWilayah')->name('peta.data.wilayah');
});

// Tamu
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

// Pengguna yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Semua role: ubah kata sandi sendiri
    Route::get('/kata-sandi', [KataSandiController::class, 'edit'])
        ->name('kata-sandi.edit');
    Route::put('/kata-sandi', [KataSandiController::class, 'update'])
        ->name('kata-sandi.update');

    // Dinas: daftar, tambah, dan hapus sekolah
    Route::middleware('role:dinas')->prefix('dinas')->name('dinas.')
        ->group(function () {
            Route::resource('sekolah', SekolahController::class)
                ->only(['index', 'create', 'store', 'destroy']);
        });

    // Dinas dan Sekolah: ubah data dan profil (dibatasi Policy)
    Route::middleware('role:dinas,sekolah')->group(function () {
        Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])
            ->name('sekolah.edit');
        Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])
            ->name('sekolah.update');

        Route::get('sekolah/{sekolah}/profil',
            [ProfilSekolahController::class, 'edit'])
            ->name('sekolah.profil.edit');
        Route::put('sekolah/{sekolah}/profil',
            [ProfilSekolahController::class, 'update'])
            ->name('sekolah.profil.update');
    });

    // Admin: kelola pengguna
    Route::middleware('role:admin')->prefix('admin')->name('admin.')
        ->group(function () {
            Route::resource('users', UserController::class)->except('show');
        });
});