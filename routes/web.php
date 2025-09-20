<?php

use App\Http\Controllers\GudangController;
use App\Http\Controllers\KategoriItemController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\SupplierController;
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
        Route::get('/{id}', [GudangController::class, 'show'])->name('gudang.show');
        Route::put('/{id}/update', [GudangController::class, 'update'])->name('gudang.update');
        Route::delete('/{id}/delete', [GudangController::class, 'destroy'])->name('gudang.destroy');
    });

    Route::prefix('supplier')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('supplier.index');
        Route::get('/create', [SupplierController::class, 'create'])->name('supplier.create');
        Route::post('/store', [SupplierController::class, 'store'])->name('supplier.store');
        Route::get('/{id}', [SupplierController::class, 'show'])->name('supplier.show');
        Route::put('/{id}/update', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/{id}/delete', [SupplierController::class, 'destroy'])->name('supplier.destroy');
    });

    

    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', function () {
            return view('auth.items.index'); // buat view items/index.blade.php
        })->name('index');
        Route::get('/create', function () {
            return view('auth.items.create'); // buat view items/create.blade.php
        })->name('create');
        Route::post('/store', function () {
            // simpan item
        })->name('store');
        Route::get('/{id}', function ($id) {
            return view('auth.items.show', compact('id')); // buat view items/show.blade.php
        })->name('show');
        Route::put('/{id}/update', function ($id) {
            // update item
        })->name('update');

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', function () {
            return view('auth.items.categories.index'); // buat view items/index.blade.php
        })->name('index');
            
            Route::get('/create', [KategoriItemController::class, 'create'])->name('create');
            Route::post('/store', [KategoriItemController::class, 'store'])->name('store');
            Route::get('/{id}', [KategoriItemController::class, 'show'])->name('show');
            Route::put('/{id}/update', [KategoriItemController::class, 'update'])->name('update');
            Route::delete('/{id}/delete', [KategoriItemController::class, 'destroy'])->name('destroy');
        });
    });

    Route::prefix('pelanggan')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::get('/create', [PelangganController::class, 'create'])->name('pelanggan.create');
        Route::post('/store', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('/{id}', [PelangganController::class, 'show'])->name('pelanggan.show');
        Route::put('/{id}/update', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('/{id}/delete', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');
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


