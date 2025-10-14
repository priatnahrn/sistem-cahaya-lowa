<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Controller Imports
use App\Http\Controllers\UserController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PenjualanCepatController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\TagihanPembelianController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\KategoriItemController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\MutasiStokController;

// ========================
// 🔹 Root Route
// ========================
Route::get('/', function () {
    return Auth::check()
        ? redirect('/dashboard')
        : redirect()->route('login');
});

// ========================
// 🔹 Guest Routes (Login)
// ========================
Route::middleware('guest')->group(function () {
    Route::get('/login', [UserController::class, 'loginPage'])->name('login');
    Route::post('/login', [UserController::class, 'login']);
});

// ========================
// 🔹 Authenticated Routes
// ========================
Route::middleware('auth')->group(function () {

    // ------------------------
    // 🏠 Dashboard
    // ------------------------
    Route::get('/dashboard', fn() => view('auth.dashboard'))->name('dashboard');

    // ------------------------
    // 🧾 Penjualan
    // ------------------------
    Route::prefix('penjualan')->name('penjualan.')->group(function () {
        Route::get('/', [PenjualanController::class, 'index'])->name('index');
        Route::get('/create', [PenjualanController::class, 'create'])->name('create');
        Route::post('/store', [PenjualanController::class, 'store'])->name('store');
        Route::get('/search', [PenjualanController::class, 'searchPenjualan'])->name('search');
        Route::get('/items/search', [PenjualanController::class, 'searchItems'])->name('items.search');
        Route::get('/{id}/print', [PenjualanController::class, 'print'])->name('print');
        Route::get('/{id}/last-price', [PenjualanController::class, 'getLastPrice'])->name('last_price');
        Route::put('/{id}/update', [PenjualanController::class, 'update'])->name('update');
        Route::put('/{id}/cancel', [PenjualanController::class, 'cancelDraft'])->name('cancel_draft');
        Route::delete('/{id}/delete', [PenjualanController::class, 'destroy'])->name('destroy');
        Route::get('/{id}', [PenjualanController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::get('/items/barcode/{barcode}', [PenjualanController::class, 'getItemByBarcode']);
    Route::get('/items/stock', [PenjualanController::class, 'getStock']);
    Route::get('/items/price', [PenjualanController::class, 'getPrice']);
    // ------------------------
    // ⚡ Penjualan Cepat
    // ------------------------
    Route::prefix('penjualan-cepat')->name('penjualan-cepat.')->group(function () {
        Route::get('/', [PenjualanCepatController::class, 'index'])->name('index');
        Route::get('/create', [PenjualanCepatController::class, 'create'])->name('create');
        Route::post('/store', [PenjualanCepatController::class, 'store'])->name('store');
        Route::put('/{id}/update', [PenjualanCepatController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [PenjualanCepatController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [PenjualanCepatController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // Retur Penjualan
    // ------------------------
    Route::prefix('penjualan/retur-penjualan')->group(function () {
        Route::get('/', [App\Http\Controllers\ReturPenjualanController::class, 'index'])
            ->name('retur-penjualan.index');

        Route::get('/create', [App\Http\Controllers\ReturPenjualanController::class, 'create'])
            ->name('retur-penjualan.create');

        Route::post('/', [App\Http\Controllers\ReturPenjualanController::class, 'store'])
            ->name('retur-penjualan.store');

        Route::get('/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'show'])
            ->name('retur-penjualan.show');

        Route::put('/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'update'])
            ->name('retur-penjualan.update');

        Route::delete('/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'destroy'])
            ->name('retur-penjualan.destroy');

        // 🔹 API: ambil item berdasarkan penjualan
        Route::get('/items/by-penjualan/{id}', [App\Http\Controllers\ReturPenjualanController::class, 'getItemsByPenjualan'])
            ->name('retur-penjualan.get-items');
    });


    // ------------------------
    // 🚚 Pengiriman
    // ------------------------
    Route::prefix('pengiriman')->name('pengiriman.')->group(function () {
        Route::get('/', [PengirimanController::class, 'index'])->name('index');
        Route::get('/create', [PengirimanController::class, 'create'])->name('create');
        Route::post('/store', [PengirimanController::class, 'store'])->name('store');
        Route::get('/search', [PengirimanController::class, 'search'])->name('search');
        Route::put('/{id}/update', [PengirimanController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [PengirimanController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [PengirimanController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 💳 Pembayaran
    // ------------------------
    Route::prefix('pembayaran')->name('pembayaran.')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])->name('index');
        Route::get('/create', [PembayaranController::class, 'create'])->name('create');
        Route::post('/', [PembayaranController::class, 'store'])->name('store');
        Route::delete('/{id}/delete', [PembayaranController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [PembayaranController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 📦 Pembelian & Tagihan
    // ------------------------
    Route::prefix('pembelian')->name('pembelian.')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('index');
        Route::get('/create', [PembelianController::class, 'create'])->name('create');
        Route::post('/', [PembelianController::class, 'store'])->name('store');
        Route::put('/{id}', [PembelianController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [PembelianController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}/items', [PembelianController::class, 'getItems'])->whereNumber('id')->name('items');
        Route::get('/{id}', [PembelianController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('pembelian/tagihan')->name('tagihan.pembelian.')->group(function () {
        Route::get('/', [TagihanPembelianController::class, 'index'])->name('index');
        Route::get('/{id}/edit', [TagihanPembelianController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}', [TagihanPembelianController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [TagihanPembelianController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [TagihanPembelianController::class, 'show'])->whereNumber('id')->name('show');
    });

    Route::prefix('pembelian/retur-pembelian')->name('retur-pembelian.')->group(function () {
        Route::get('/', [ReturPembelianController::class, 'index'])->name('index');
        Route::get('/create', [ReturPembelianController::class, 'create'])->name('create');
        Route::post('/', [ReturPembelianController::class, 'store'])->name('store');
        Route::put('/{id}', [ReturPembelianController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [ReturPembelianController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [ReturPembelianController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 🧰 Gudang
    // ------------------------
    Route::prefix('gudang')->name('gudang.')->group(function () {
        Route::get('/', [GudangController::class, 'index'])->name('index');
        Route::get('/create', [GudangController::class, 'create'])->name('create');
        Route::post('/store', [GudangController::class, 'store'])->name('store');
        Route::put('/{id}/update', [GudangController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [GudangController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [GudangController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 🧑‍💼 Supplier
    // ------------------------
    Route::prefix('supplier')->name('supplier.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/create', [SupplierController::class, 'create'])->name('create');
        Route::post('/store', [SupplierController::class, 'store'])->name('store');
        Route::get('/search', [SupplierController::class, 'search'])->name('search');
        Route::put('/{id}/update', [SupplierController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [SupplierController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [SupplierController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 👥 Pelanggan
    // ------------------------
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('index');
        Route::get('/create', [PelangganController::class, 'create'])->name('create');
        Route::post('/store', [PelangganController::class, 'store'])->name('store');
        Route::get('/search', [PelangganController::class, 'search'])->name('search');
        Route::put('/{id}/update', [PelangganController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [PelangganController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [PelangganController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 📦 Items + Categories
    // ------------------------
    Route::prefix('items')->name('items.')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/create', [ItemController::class, 'create'])->name('create');
        Route::post('/store', [ItemController::class, 'store'])->name('store');
        Route::get('/search', [ItemController::class, 'search'])->name('search');

        // Nested Category Routes
        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [KategoriItemController::class, 'index'])->name('index');
            Route::get('/create', [KategoriItemController::class, 'create'])->name('create');
            Route::post('/store', [KategoriItemController::class, 'store'])->name('store');
            Route::put('/{id}/update', [KategoriItemController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [KategoriItemController::class, 'destroy'])->whereNumber('id')->name('destroy');
            Route::get('/{id}', [KategoriItemController::class, 'show'])->whereNumber('id')->name('show');
        });

        Route::put('/{id}/update', [ItemController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}', [ItemController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [ItemController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 🏭 Produksi
    // ------------------------
    Route::prefix('produksi')->name('produksi.')->group(function () {
        Route::get('/', [ProduksiController::class, 'index'])->name('index');
        Route::put('/{id}/update', [ProduksiController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [ProduksiController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [ProduksiController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 🔄 Mutasi Stok
    // ------------------------
    Route::prefix('mutasi-stok')->name('mutasi-stok.')->group(function () {
        Route::get('/', [MutasiStokController::class, 'index'])->name('index');
        Route::get('/create', [MutasiStokController::class, 'create'])->name('create');
        Route::post('/store', [MutasiStokController::class, 'store'])->name('store');
        Route::put('/{id}/update', [MutasiStokController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}/delete', [MutasiStokController::class, 'destroy'])->whereNumber('id')->name('destroy');
        Route::get('/{id}', [MutasiStokController::class, 'show'])->whereNumber('id')->name('show');
    });

    // ------------------------
    // 👤 Profil
    // ------------------------
    Route::get('/profil', fn() => view('auth.profil.index'))->name('profil.index');

    // ------------------------
    // 🚪 Logout
    // ------------------------
    Route::post('/logout', [UserController::class, 'keluar'])->name('logout');
});

// ========================
// 🔹 Fallback (404)
// ========================
Route::fallback(fn() => response()->view('errors.404', [], 404));
