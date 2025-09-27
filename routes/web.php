<?php

use App\Http\Controllers\GudangController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\KategoriItemController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PenjualanController;
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
        Route::get('/', [PenjualanController::class, 'index'])->name('penjualan.index');
        Route::get('/create', [PenjualanController::class, 'create'])->name('penjualan.create');
        Route::post('/store', [PenjualanController::class, 'store'])->name('penjualan.store');
        Route::get('/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
        Route::put('/{id}/update', [PenjualanController::class, 'update'])->name('penjualan.update');
        Route::get('/{id}/last-price', [PenjualanController::class, 'getLastPrice'])->name('penjualan.last_price');
        Route::delete('/{id}/delete', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');
    });

    Route::prefix('pembelian')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('pembelian.index');
        Route::get('/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::post('/store', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/{id}', [PembelianController::class, 'show'])->name('pembelian.show');
        Route::put('/{id}/update', [PembelianController::class, 'update'])->name('pembelian.update');
        Route::get('/{id}/last-price', [PembelianController::class, 'getLastPrice'])->name('pembelian.last_price');
        Route::delete('/{id}/delete', [PembelianController::class, 'destroy'])->name('pembelian.destroy');
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
        Route::get('/search', [SupplierController::class, 'search'])->name('supplier.search');
        Route::get('/{id}', [SupplierController::class, 'show'])->name('supplier.show');
        Route::put('/{id}/update', [SupplierController::class, 'update'])->name('supplier.update');
        Route::delete('/{id}/delete', [SupplierController::class, 'destroy'])->name('supplier.destroy');
    });



    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/store', [ItemController::class, 'store'])->name('store');
        Route::get('/search', [ItemController::class, 'search'])->name('search');
        
        
        
        // categories harus di sini, sebelum /{id}
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [KategoriItemController::class, 'index'])->name('index');
            Route::get('/create', [KategoriItemController::class, 'create'])->name('create');
            Route::post('/store', [KategoriItemController::class, 'store'])->name('store');
            Route::get('/{id}', [KategoriItemController::class, 'show'])->name('show');
            Route::put('/{id}/update', [KategoriItemController::class, 'update'])->name('update');
            Route::delete('/{id}', [KategoriItemController::class, 'destroy'])->name('destroy');
        });
        
        // route dinamis item setelah categories
        Route::get('/by-barcode/{kode}', [ItemController::class, 'findByBarcode'])->name('scan');
        Route::get('{id}/prices', [ItemController::class, 'getPrices'])->name('prices');
        Route::get('/{id}', [ItemController::class, 'show'])->name('show');
        Route::put('/{id}/update', [ItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [ItemController::class, 'destroy'])->name('destroy');
    });


    Route::prefix('pelanggan')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::get('/create', [PelangganController::class, 'create'])->name('pelanggan.create');
        Route::post('/store', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('/search', [PelangganController::class, 'search'])->name('pelanggan.search');
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
