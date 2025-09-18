<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;

// root → redirect dinamis
Route::get('/', function () {
    return Auth::check()
        ? redirect('/dashboard')
        : redirect()->route('login');
});

// auth pages
Route::middleware('guest')->group(function () {
    Route::get('/login', [UserController::class, 'loginPage'])->name('login');
    Route::post('/login', [UserController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    // contoh dashboard
    Route::get('/dashboard', function () {
        return view('auth.dashboard'); // buat view dashboard.blade.php
    })->name('dashboard');

    Route::get('/penjualan', function () {
        return view('auth.penjualan.index'); // buat view penjualan/index.blade.php
    })->name('penjualan.index');
    Route::get('/penjualan/create', function () {
        return view('auth.penjualan.create'); // buat view penjualan/create.blade.php
    })->name('penjualan.create');

    Route::get('/profil', function () {
        return view('auth.profil.index'); // buat view profil/index.blade.php
    })->name('profil.index');

    Route::post('/logout', [UserController::class, 'keluar'])->name('logout');
});

// fallback 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
