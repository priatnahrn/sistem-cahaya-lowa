<?php

use App\Http\Controllers\GudangController;
use App\Http\Controllers\KategoriItemController;
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

    Route::prefix('penjualan')->group(function () {
        Route::get('/', function () {
            return view('auth.penjualan.index'); // buat view penjualan/index.blade.php
        })->name('penjualan.index');
        Route::get('/create', function () {
            return view('auth.penjualan.create'); // buat view penjualan/create.blade.php
        })->name('penjualan.create');
    });

    Route::prefix('gudang')->group(function () {
        Route::get('/', [GudangController::class, 'index'])->name('gudang.index');
        Route::get('/create', [GudangController::class, 'create'])->name('gudang.create');
        Route::post('/store', [GudangController::class, 'store'])->name('gudang.store');
    });

    Route::prefix('supplier')->group(function () {
        Route::get('/', function () {
            return view('auth.supplier.index'); // buat view supplier/index.blade.php
        })->name('supplier.index');
        Route::get('/create', function () {
            return view('auth.supplier.create'); // buat view supplier/create.blade.php
        })->name('supplier.create');
    });

    

    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', function () {
            return view('auth.items.index'); // buat view items/index.blade.php
        })->name('index');
        Route::get('/create', function () {
            return view('auth.items.create'); // buat view items/create.blade.php
        })->name('create');

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [KategoriItemController::class, 'index'])->name('index');
            Route::get('/create', [KategoriItemController::class, 'create'])->name('create');
            Route::post('/store', [KategoriItemController::class, 'store'])->name('store');
            Route::get('/{id}', [KategoriItemController::class, 'show'])->name('show');
            Route::put('/{id}/update', [KategoriItemController::class, 'update'])->name('update');
        });
    });

    Route::prefix('pelanggan')->group(function () {
        Route::get('/', function () {
            return view('auth.pelanggan.index'); // buat view pelanggan/index.blade.php
        })->name('pelanggan.index');
        Route::get('/create', function () {
            return view('auth.pelanggan.create'); // buat view pelanggan/create.blade.php
        })->name('pelanggan.create');
    });

    Route::get('/profil', function () {
        return view('auth.profil.index'); // buat view profil/index.blade.php
    })->name('profil.index');

    Route::post('/logout', [UserController::class, 'keluar'])->name('logout');
});

// fallback 404
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
