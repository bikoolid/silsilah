<?php

use App\Http\Controllers\PersonController;
use App\Http\Controllers\CoupleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\LoginController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/anggota-keluarga', [PersonController::class, 'index'])->name('people.index');
    Route::get('/anggota-keluarga/create', [PersonController::class, 'create'])->name('people.create');
    Route::get('/anggota-keluarga/{person}', [PersonController::class, 'show'])->name('people.show');
    Route::post('/anggota-keluarga', [PersonController::class, 'store'])->name('people.store');
    Route::get('/anggota-keluarga/{person}/edit', [PersonController::class, 'edit'])->name('people.edit');
    Route::get('/pasangan', [CoupleController::class, 'index'])->name('couples.index');
    Route::put('/anggota-keluarga/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::delete('/anggota-keluarga/{person}', [PersonController::class, 'destroy'])->name('people.destroy');
    Route::put('/anggota-keluarga/{person}/parents', [PersonController::class, 'updateParents'])->name('people.parents.update');
    Route::post('/anggota-keluarga/{person}/couples', [CoupleController::class, 'store'])->name('couples.store');
    Route::post('/couples', [CoupleController::class, 'store'])->name('couples.create');
    Route::put('/couples/{couple}', [CoupleController::class, 'update'])->name('couples.update');
    Route::post('/couples/{couple}/children', [CoupleController::class, 'addChild'])->name('couples.children.store');
    Route::delete('/couples/{couple}', [CoupleController::class, 'destroy'])->name('couples.destroy');
    Route::get('/silsilah/{person?}', [PersonController::class, 'tree'])->name('family-tree');
    Route::get('/peta-makam', [PersonController::class, 'cemeteryMap'])->name('cemetery-map');
    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings');
    Route::put('/pengaturan/profil', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::post('/pengaturan/pengelola', [SettingsController::class, 'storeUser'])->name('settings.users.store');
    Route::delete('/pengaturan/pengelola/{user}', [SettingsController::class, 'destroyUser'])->name('settings.users.destroy');
    Route::get('/pengaturan/backup', [SettingsController::class, 'downloadBackup'])->name('settings.backup.download');
    Route::post('/pengaturan/backup/preview', [SettingsController::class, 'inspectBackup'])->name('settings.backup.preview');
    Route::post('/pengaturan/backup/restore', [SettingsController::class, 'restoreBackup'])->name('settings.backup.restore');
});
