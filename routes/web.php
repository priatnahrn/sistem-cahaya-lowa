<?php

use App\Http\Controllers\GudangController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\KategoriItemController;
use App\Http\Controllers\MutasiStokController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\PenjualanCepatController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TagihanPembelianController;
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
        Route::get('/search', [PenjualanController::class, 'searchPenjualan'])->name('penjualan.search');
        Route::get('/{id}', [PenjualanController::class, 'show'])->name('penjualan.show');
        Route::get('/items/search', [PenjualanController::class, 'searchItems']);

        // Print universal (pakai ?type=kecil / ?type=besar)
        Route::get('/{id}/print', [PenjualanController::class, 'print'])->name('penjualan.print');

        Route::put('/{id}/update', [PenjualanController::class, 'update'])->name('penjualan.update');
        Route::get('/{id}/last-price', [PenjualanController::class, 'getLastPrice'])->name('penjualan.last_price');
        Route::delete('/{id}/delete', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');
    });

    Route::prefix('penjualan-cepat')->group(function () {
        Route::get('/', [PenjualanCepatController::class, 'index'])->name('penjualan-cepat.index');
        Route::get('/create', [PenjualanCepatController::class, 'create'])->name('penjualan-cepat.create');
        Route::post('/store', [PenjualanCepatController::class, 'store'])->name('penjualan-cepat.store');
        Route::get('/{id}', [PenjualanCepatController::class, 'show'])->name('penjualan-cepat.show');
        Route::put('/{id}/update', [PenjualanCepatController::class, 'update'])->name('penjualan-cepat.update');
        Route::delete('/{id}/delete', [PenjualanCepatController::class, 'destroy'])->name('penjualan-cepat.destroy');
    });

    Route::prefix('pengiriman')->group(function () {
        Route::get('/', [PengirimanController::class, 'index'])->name('pengiriman.index');
        Route::get('/create', [PengirimanController::class, 'create'])->name('pengiriman.create');
        Route::post('/store', [PengirimanController::class, 'store'])->name('pengiriman.store');
        Route::get('/{id}', [PengirimanController::class, 'show'])->name('pengiriman.show');
        Route::put('/{id}/update', [PengirimanController::class, 'update'])->name('pengiriman.update');
        Route::delete('/{id}/delete', [PengirimanController::class, 'destroy'])->name('pengiriman.destroy');
    });

    Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
        // Halaman daftar pembayaran
        Route::get('/', [PembayaranController::class, 'index'])->name('index');

        // Form tambah pembayaran
        Route::get('/create', [PembayaranController::class, 'create'])->name('create');

        // Simpan pembayaran baru
        Route::post('/', [PembayaranController::class, 'store'])->name('store');

        // Detail pembayaran tertentu
        Route::get('/{id}', [PembayaranController::class, 'show'])->name('show');

        // Hapus pembayaran (AJAX / fetch delete)
        Route::delete('/{id}/delete', [PembayaranController::class, 'destroy'])->name('destroy');
    });


    Route::get('/items/barcode/{barcode}', [PenjualanController::class, 'getItemByBarcode']);
    Route::get('/items/stock', [PenjualanController::class, 'getStock']);
    Route::get('/items/price', [PenjualanController::class, 'getPrice']);

    Route::prefix('pembelian/tagihan')->group(function () {
        Route::get('/', [TagihanPembelianController::class, 'index'])->name('tagihan.pembelian.index');
        Route::get('/{id}', [TagihanPembelianController::class, 'show'])->name('tagihan.pembelian.show')->whereNumber('id');
        Route::get('/{id}/edit', [TagihanPembelianController::class, 'edit'])->name('tagihan.pembelian.edit')->whereNumber('id');
        Route::put('/{id}', [TagihanPembelianController::class, 'update'])->name('tagihan.pembelian.update')->whereNumber('id');
        Route::delete('/{id}', [TagihanPembelianController::class, 'destroy'])->name('tagihan.pembelian.destroy')->whereNumber('id');
    });


    // 📌 Retur Pembelian
    Route::prefix('pembelian/retur-pembelian')->group(function () {
        Route::get('/', [ReturPembelianController::class, 'index'])->name('retur-pembelian.index');
        Route::get('/create', [ReturPembelianController::class, 'create'])->name('retur-pembelian.create');
        Route::post('/', [ReturPembelianController::class, 'store'])->name('retur-pembelian.store');
        Route::get('/{id}', [ReturPembelianController::class, 'show'])->name('retur-pembelian.show');
        Route::put('/{id}', [ReturPembelianController::class, 'update'])->name('retur-pembelian.update');
        Route::delete('/{id}', [ReturPembelianController::class, 'destroy'])->name('retur-pembelian.destroy');
    });


    // 📌 Pembelian
    Route::prefix('pembelian')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('pembelian.index');
        Route::get('/create', [PembelianController::class, 'create'])->name('pembelian.create');
        Route::post('/', [PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/{id}', [PembelianController::class, 'show'])->name('pembelian.show')->whereNumber('id');
        Route::put('/{id}', [PembelianController::class, 'update'])->name('pembelian.update')->whereNumber('id');
        Route::delete('/{id}', [PembelianController::class, 'destroy'])->name('pembelian.destroy')->whereNumber('id');

        // 📌 API khusus untuk ambil item pembelian
        Route::get('/{id}/items', [PembelianController::class, 'getItems'])
            ->name('pembelian.items')
            ->whereNumber('id');
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

    Route::prefix('pelanggan')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::get('/create', [PelangganController::class, 'create'])->name('pelanggan.create');
        Route::post('/store', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('/search', [PelangganController::class, 'search'])->name('pelanggan.search');
        Route::get('/{id}', [PelangganController::class, 'show'])->name('pelanggan.show');
        Route::put('/{id}/update', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('/{id}/delete', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');
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

        Route::get('/{id}', [ItemController::class, 'show'])->name('show');
        Route::put('/{id}/update', [ItemController::class, 'update'])->name('update');
        Route::delete('/{id}', [ItemController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/', [ProduksiController::class, 'index'])->name('index'); 
    });

    Route::prefix('mutasi-stok')->name('mutasi-stok.')->group(function () {
        Route::get('/', [MutasiStokController::class, 'index'])->name('index');
        Route::get('/create', [MutasiStokController::class, 'create'])->name('create');
        Route::post('/store', [MutasiStokController::class, 'store'])->name('store');
        Route::get('/{id}', [MutasiStokController::class, 'show'])->name('show');
        Route::put('/{id}/update', [MutasiStokController::class, 'update'])->name('update');
        Route::delete('/{id}/delete', [MutasiStokController::class, 'destroy'])->name('destroy');
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
