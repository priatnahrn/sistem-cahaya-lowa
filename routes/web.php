<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GajiController;
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
use App\Http\Controllers\KasKeuanganController;
use App\Http\Controllers\LogActivityController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\MutasiStokController;
use App\Http\Controllers\ReturPenjualanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TagihanPenjualanController;
use App\Http\Controllers\UserManagementController;

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

Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    // Cari halaman pertama yang accessible
    $accessiblePages = [
        ['permission' => 'dashboard.view', 'route' => 'dashboard'],
        ['permission' => 'penjualan_cepat.view', 'route' => 'penjualan-cepat.index'],
        ['permission' => 'pembayaran.view', 'route' => 'pembayaran.index'],
        ['permission' => 'penjualan.view', 'route' => 'penjualan.index'],
        ['permission' => 'pembelian.view', 'route' => 'pembelian.index'],
        ['permission' => 'gudang.view', 'route' => 'gudang.index'],
        ['permission' => 'supplier.view', 'route' => 'supplier.index'],
        ['permission' => 'items.view', 'route' => 'items.index'],
        ['permission' => 'pelanggan.view', 'route' => 'pelanggan.index'],
        ['permission' => 'cashflows.view', 'route' => 'kas-keuangan.index'],
        ['permission' => 'payrolls.view', 'route' => 'gaji-karyawan.index'],
        ['permission' => 'users.view', 'route' => 'users.index'],
        ['permission' => 'roles.view', 'route' => 'roles.index'],
    ];

    foreach ($accessiblePages as $page) {
        if ($user->can($page['permission'])) {
            return redirect()->route($page['route']);
        }
    }

    // Fallback ke profil
    return redirect()->route('profil.index');
});
// ========================
// 🔹 Authenticated Routes
// ========================
Route::middleware('auth')->group(function () {

    // ------------------------
    // 🏠 Dashboard
    // ------------------------
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------
    // 🔐 User Management
    // ------------------------
    Route::prefix('users')->name('users.')->middleware('permission:users.view')->group(function () {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');

        Route::middleware('permission:users.create')->group(function () {
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
        });

        Route::get('/{user}/edit', [UserManagementController::class, 'edit'])
            ->middleware('permission:users.update')
            ->name('edit');

        Route::put('/{user}', [UserManagementController::class, 'update'])
            ->middleware('permission:users.update')
            ->name('update');

        Route::delete('/{user}', [UserManagementController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('destroy');
    });

    // ------------------------
    // 🛡️ Role Management
    // ------------------------
    Route::prefix('roles')->name('roles.')->middleware('permission:roles.view')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');

        Route::middleware('permission:roles.create')->group(function () {
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
        });

        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::middleware('permission:roles.update')->group(function () {
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::post('/{role}/assign-permissions', [RoleController::class, 'assignPermissions'])
                ->name('assign-permissions');
        });

        Route::delete('/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('destroy');
    });

    // ------------------------
    // 🧾 Penjualan
    // ------------------------
    Route::prefix('penjualan')->name('penjualan.')->middleware('permission:penjualan.view')->group(function () {
        // View routes
        Route::get('/', [PenjualanController::class, 'index'])->name('index');
        Route::get('/{id}', [PenjualanController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/search', [PenjualanController::class, 'searchPenjualan'])->name('search');
        Route::get('/{id}/print', [PenjualanController::class, 'print'])->name('print');
        Route::get('/{id}/last-price', [PenjualanController::class, 'getLastPrice'])->name('last_price');

        // Helper routes (tidak perlu permission khusus, cukup view)
        Route::get('/items/search', [PenjualanController::class, 'searchItems'])->name('items.search');

        // Create routes
        Route::middleware('permission:penjualan.create')->group(function () {
            Route::get('/create', [PenjualanController::class, 'create'])->name('create');
            Route::post('/store', [PenjualanController::class, 'store'])->name('store');
        });

        // Update routes
        Route::middleware('permission:penjualan.update')->group(function () {
            Route::put('/{id}/update', [PenjualanController::class, 'update'])->name('update');
            Route::delete('/{id}/cancel', [PenjualanController::class, 'cancelDraft'])->name('cancel_draft');
        });

        // Delete routes
        Route::delete('/{id}/delete', [PenjualanController::class, 'destroy'])
            ->middleware('permission:penjualan.delete')
            ->name('destroy');
    });

    // Helper routes untuk items (bisa diakses siapa saja yang login)
    Route::get('/items/barcode/{barcode}', [PenjualanController::class, 'getItemByBarcode']);
    Route::get('/items/stock', [PenjualanController::class, 'getStock']);
    Route::get('/items/price', [PenjualanController::class, 'getPrice']);

    // ------------------------
    // ⚡ Penjualan Cepat
    // ------------------------
    Route::prefix('penjualan-cepat')->name('penjualan-cepat.')->middleware('permission:penjualan_cepat.view')->group(function () {
        Route::get('/', [PenjualanCepatController::class, 'index'])->name('index');
        Route::get('/{id}', [PenjualanCepatController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware('permission:penjualan_cepat.create')->group(function () {
            Route::get('/create', [PenjualanCepatController::class, 'create'])->name('create');
            Route::post('/store', [PenjualanCepatController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [PenjualanCepatController::class, 'update'])
            ->middleware('permission:penjualan_cepat.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [PenjualanCepatController::class, 'destroy'])
            ->middleware('permission:penjualan_cepat.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 🔄 Retur Penjualan
    // ------------------------
    Route::prefix('penjualan/retur-penjualan')->name('retur-penjualan.')->middleware('permission:retur_penjualan.view')->group(function () {
        Route::get('/', [ReturPenjualanController::class, 'index'])->name('index');

        Route::middleware('permission:retur_penjualan.create')->group(function () {
            Route::get('/create', [ReturPenjualanController::class, 'create'])->name('create');
            Route::post('/', [ReturPenjualanController::class, 'store'])->name('store');
        });
        Route::get('/{id}', [ReturPenjualanController::class, 'show'])->name('show');
        Route::get('/items/by-penjualan/{id}', [ReturPenjualanController::class, 'getItemsByPenjualan'])->name('get-items');

        Route::put('/{id}', [ReturPenjualanController::class, 'update'])
            ->middleware('permission:retur_penjualan.update')
            ->name('update');

        Route::delete('/{id}', [ReturPenjualanController::class, 'destroy'])
            ->middleware('permission:retur_penjualan.delete')
            ->name('destroy');
    });

    // ------------------------
    // 💰 Tagihan Penjualan
    // ------------------------
    Route::prefix('kasir/tagihan-penjualan')->name('tagihan-penjualan.')->middleware('permission:tagihan_penjualan.view')->group(function () {
        Route::get('/', [TagihanPenjualanController::class, 'index'])->name('index');
        Route::get('/{id}', [TagihanPenjualanController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware('permission:tagihan_penjualan.update')->group(function () {
            Route::get('/{id}/edit', [TagihanPenjualanController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [TagihanPenjualanController::class, 'update'])->whereNumber('id')->name('update');
        });

        Route::delete('/{id}', [TagihanPenjualanController::class, 'destroy'])
            ->middleware('permission:tagihan_penjualan.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 🚚 Pengiriman
    // ------------------------
    Route::prefix('pengiriman')->name('pengiriman.')->middleware('permission:pengiriman.view')->group(function () {
        Route::get('/', [PengirimanController::class, 'index'])->name('index');
        Route::get('/{id}', [PengirimanController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/search', [PengirimanController::class, 'search'])->name('search');

        Route::middleware('permission:pengiriman.create')->group(function () {
            Route::get('/create', [PengirimanController::class, 'create'])->name('create');
            Route::post('/store', [PengirimanController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [PengirimanController::class, 'update'])
            ->middleware('permission:pengiriman.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [PengirimanController::class, 'destroy'])
            ->middleware('permission:pengiriman.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 💳 Pembayaran
    // ------------------------
    Route::prefix('pembayaran')->name('pembayaran.')->middleware('permission:pembayaran.view')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])->name('index');
        Route::get('/{id}', [PembayaranController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware('permission:pembayaran.create')->group(function () {
            Route::get('/create', [PembayaranController::class, 'create'])->name('create');
            Route::post('/', [PembayaranController::class, 'store'])->name('store');
        });

        Route::delete('/{id}/delete', [PembayaranController::class, 'destroy'])
            ->middleware('permission:pembayaran.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 📦 Pembelian
    // ------------------------
    Route::prefix('pembelian')->name('pembelian.')->middleware('permission:pembelian.view')->group(function () {
        Route::get('/', [PembelianController::class, 'index'])->name('index');
        Route::get('/{id}', [PembelianController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/items', [PembelianController::class, 'getItems'])->whereNumber('id')->name('items');

        Route::middleware('permission:pembelian.create')->group(function () {
            Route::get('/create', [PembelianController::class, 'create'])->name('create');
            Route::post('/', [PembelianController::class, 'store'])->name('store');
        });

        Route::put('/{id}', [PembelianController::class, 'update'])
            ->middleware('permission:pembelian.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [PembelianController::class, 'destroy'])
            ->middleware('permission:pembelian.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 💵 Tagihan Pembelian
    // ------------------------
    Route::prefix('pembelian/tagihan-pembelian')->name('tagihan-pembelian.')->middleware('permission:tagihan_pembelian.view')->group(function () {
        Route::get('/', [TagihanPembelianController::class, 'index'])->name('index');
        Route::get('/{id}', [TagihanPembelianController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware('permission:tagihan_pembelian.update')->group(function () {
            Route::get('/{id}/edit', [TagihanPembelianController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [TagihanPembelianController::class, 'update'])->whereNumber('id')->name('update');
        });

        Route::delete('/{id}', [TagihanPembelianController::class, 'destroy'])
            ->middleware('permission:tagihan_pembelian.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 🔙 Retur Pembelian
    // ------------------------
    Route::prefix('pembelian/retur-pembelian')->name('retur-pembelian.')->middleware('permission:retur_pembelian.view')->group(function () {
        Route::get('/', [ReturPembelianController::class, 'index'])->name('index');

        Route::middleware('permission:retur_pembelian.create')->group(function () {
            Route::get('/create', [ReturPembelianController::class, 'create'])->name('create');
            Route::post('/store', [ReturPembelianController::class, 'store'])->name('store');
        });
        Route::get('/{id}', [ReturPembelianController::class, 'show'])->name('show');

        Route::put('/{id}', [ReturPembelianController::class, 'update'])
            ->middleware('permission:retur_pembelian.update')
            ->name('update');

        Route::delete('/{id}', [ReturPembelianController::class, 'destroy'])
            ->middleware('permission:retur_pembelian.delete')
            ->name('destroy');
    });

    // ------------------------
    // 🧰 Gudang
    // ------------------------
    Route::prefix('gudang')->name('gudang.')->middleware('permission:gudang.view')->group(function () {
        Route::get('/', [GudangController::class, 'index'])->name('index');
        Route::get('/{id}', [GudangController::class, 'show'])->whereNumber('id')->name('show');

        Route::middleware('permission:gudang.create')->group(function () {
            Route::get('/create', [GudangController::class, 'create'])->name('create');
            Route::post('/store', [GudangController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [GudangController::class, 'update'])
            ->middleware('permission:gudang.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [GudangController::class, 'destroy'])
            ->middleware('permission:gudang.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 🧑‍💼 Supplier
    // ------------------------
    Route::prefix('supplier')->name('supplier.')->middleware('permission:supplier.view')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::get('/{id}', [SupplierController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/search', [SupplierController::class, 'search'])->name('search');

        Route::middleware('permission:supplier.create')->group(function () {
            Route::get('/create', [SupplierController::class, 'create'])->name('create');
            Route::post('/store', [SupplierController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [SupplierController::class, 'update'])
            ->middleware('permission:supplier.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [SupplierController::class, 'destroy'])
            ->middleware('permission:supplier.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 👥 Pelanggan
    // ------------------------
    Route::prefix('pelanggan')->name('pelanggan.')->middleware('permission:pelanggan.view')->group(function () {
        Route::get('/', [PelangganController::class, 'index'])->name('index');
        Route::get('/{id}', [PelangganController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/search', [PelangganController::class, 'search'])->name('search');

        Route::middleware('permission:pelanggan.create')->group(function () {
            Route::get('/create', [PelangganController::class, 'create'])->name('create');
            Route::post('/store', [PelangganController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [PelangganController::class, 'update'])
            ->middleware('permission:pelanggan.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [PelangganController::class, 'destroy'])
            ->middleware('permission:pelanggan.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 📦 Items
    // ------------------------
    Route::prefix('items')->name('items.')->middleware('permission:items.view')->group(function () {
        Route::get('/', [ItemController::class, 'index'])->name('index');
        Route::get('/{id}', [ItemController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/search', [ItemController::class, 'search'])->name('search');

        Route::middleware('permission:items.create')->group(function () {
            Route::get('/create', [ItemController::class, 'create'])->name('create');
            Route::post('/store', [ItemController::class, 'store'])->name('store');
        });

        Route::put('/{id}/update', [ItemController::class, 'update'])
            ->middleware('permission:items.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [ItemController::class, 'destroy'])
            ->middleware('permission:items.delete')
            ->whereNumber('id')
            ->name('destroy');

        // ------------------------
        // 📂 Kategori Items (Nested)
        // ------------------------
        Route::prefix('categories')->name('categories.')->middleware('permission:kategori_items.view')->group(function () {
            Route::get('/', [KategoriItemController::class, 'index'])->name('index');

            Route::middleware('permission:kategori_items.create')->group(function () {
                Route::get('/create', [KategoriItemController::class, 'create'])->name('create');
                Route::post('/store', [KategoriItemController::class, 'store'])->name('store');
            });
            Route::get('/{id}', [KategoriItemController::class, 'show'])->whereNumber('id')->name('show');

            Route::put('/{id}/update', [KategoriItemController::class, 'update'])
                ->middleware('permission:kategori_items.update')
                ->whereNumber('id')
                ->name('update');

            Route::delete('/{id}', [KategoriItemController::class, 'destroy'])
                ->middleware('permission:kategori_items.delete')
                ->whereNumber('id')
                ->name('destroy');
        });
    });

    // ------------------------
    // 🏭 Produksi
    // ------------------------
    Route::prefix('produksi')->name('produksi.')->middleware('permission:produksi.view')->group(function () {
        Route::get('/', [ProduksiController::class, 'index'])->name('index');
        Route::get('/{id}', [ProduksiController::class, 'show'])->whereNumber('id')->name('show');

        // ✅ Ubah ini (hapus /update)
        Route::put('/{id}', [ProduksiController::class, 'update'])
            ->middleware('permission:produksi.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}', [ProduksiController::class, 'destroy'])
            ->middleware('permission:produksi.delete')
            ->whereNumber('id')
            ->name('destroy');
    });
    // ------------------------
    // 🔄 Mutasi Stok
    // ------------------------
    Route::prefix('mutasi-stok')->name('mutasi-stok.')->middleware('permission:mutasi_stok.view')->group(function () {
        Route::get('/', [MutasiStokController::class, 'index'])->name('index');

        Route::middleware('permission:mutasi_stok.create')->group(function () {
            Route::get('/create', [MutasiStokController::class, 'create'])->name('create');
            Route::post('/store', [MutasiStokController::class, 'store'])->name('store');
        });

        Route::get('/{id}', [MutasiStokController::class, 'show'])->whereNumber('id')->name('show');
        Route::put('/{id}/update', [MutasiStokController::class, 'update'])
            ->middleware('permission:mutasi_stok.update')
            ->whereNumber('id')
            ->name('update');

        Route::delete('/{id}/delete', [MutasiStokController::class, 'destroy'])
            ->middleware('permission:mutasi_stok.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 📦 Kas Keuangan
    // ------------------------
    Route::prefix('kas-keuangan')->name('kas-keuangan.')->middleware('permission:cashflows.view')->group(function () {
        // 📋 Daftar kas keuangan
        Route::get('/', [KasKeuanganController::class, 'index'])->name('index');

        // ➕ Tambah kas manual (pemasukan/pengeluaran internal)
        Route::middleware('permission:cashflows.create')->group(function () {
            Route::get('/create', [KasKeuanganController::class, 'create'])->name('create');
            Route::post('/store', [KasKeuanganController::class, 'store'])->name('store');
        });

        // 📊 Laporan kas
        Route::get('/laporan', [KasKeuanganController::class, 'laporan'])->name('laporan');

        // 🧾 Detail kas
        Route::get('/{id}', [KasKeuanganController::class, 'show'])
            ->whereNumber('id')
            ->name('show');

        // 🔢 Hitung saldo kas (penyesuaian kas fisik)
        Route::post('/hitung-saldo', [KasKeuanganController::class, 'hitungSaldo'])
            ->middleware('permission:cashflows.create')
            ->name('hitung-saldo');

        // ❌ Hapus kas (hanya kas manual)
        Route::delete('/{id}', [KasKeuanganController::class, 'destroy'])
            ->middleware('permission:cashflows.delete')
            ->whereNumber('id')
            ->name('destroy');
    });

    // ------------------------
    // 💼 Gaji Karyawan
    // ------------------------
    Route::prefix('gaji-karyawan')->name('gaji-karyawan.')->middleware('permission:payrolls.view')->group(function () {
        // Halaman index
        Route::get('/', [GajiController::class, 'index'])->name('index');

        // API untuk frontend
        Route::get('/data', [GajiController::class, 'getData'])->name('data');

        // Simpan transaksi
        Route::post('/simpan', [GajiController::class, 'simpan'])->name('simpan');

        // Hapus transaksi
        Route::delete('/{id}', [GajiController::class, 'destroy'])->name('destroy');

        // Laporan mingguan
        Route::get('/laporan/mingguan', [GajiController::class, 'laporanMingguan'])->name('laporan-mingguan');

        // Detail per karyawan (taruh paling bawah biar gak bentrok)
        Route::get('/{namaKaryawan}', [GajiController::class, 'show'])->name('show');
    });


    Route::prefix('log-activity')
        ->name('log-activity.')
        ->middleware('permission:activity_logs.view')
        ->group(function () {
            // Tampilkan daftar log
            Route::get('/', [LogActivityController::class, 'index'])->name('index');
            Route::post('/', [LogActivityController::class, 'store'])
                ->name('store');
            // Detail log tertentu
            Route::get('/{logActivity}', [LogActivityController::class, 'show'])->name('show');

            // Hapus log tertentu
            Route::delete('/{logActivity}', [LogActivityController::class, 'destroy'])->name('destroy');

            // Hapus log lama berdasarkan jumlah hari
            Route::post('/delete-old', [LogActivityController::class, 'deleteOldLogs'])
                ->name('delete-old')
                ->middleware('permission:activity_logs.delete'); // opsional, bisa kamu atur sesuai permission sistemmu
        });

    // ------------------------
    // 👤 Profil (Semua user bisa akses profil sendiri)
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
