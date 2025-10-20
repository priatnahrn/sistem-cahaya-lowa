<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Pelanggan;
use App\Models\Supplier;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ✅ Cek apakah user punya akses dashboard
        if (!Auth::user()->can('dashboard.view')) {
            return $this->redirectToFirstAccessiblePage();
        }

        // Set locale to Indonesia
        Carbon::setLocale('id');

        // 📊 Total Penjualan Hari Ini
        $totalPenjualanHariIni = Penjualan::whereDate('tanggal', today())
            ->sum('total');

        // 📊 Total Penjualan Bulan Ini
        $totalPenjualanBulanIni = Penjualan::whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('total');

        // 📊 Jumlah Transaksi Hari Ini
        $jumlahTransaksiHariIni = Penjualan::whereDate('tanggal', today())->count();

        // 📊 Jumlah Pelanggan
        $jumlahPelanggan = Pelanggan::count();

        // 📊 Jumlah Supplier
        $jumlahSupplier = Supplier::count();

        // 📊 Total Pembayaran Hari Ini
        $totalPembayaranHariIni = Pembayaran::whereDate('tanggal', today())
            ->sum('jumlah_bayar');

        // 📊 Status Pembayaran (Lunas vs Belum Lunas)
        $statusPembayaran = Penjualan::select('status_bayar', DB::raw('count(*) as total'))
            ->groupBy('status_bayar')
            ->get();

        // 📊 Data Penjualan Per Hari (7 hari terakhir) untuk grafik
        $penjualanPerHari = Penjualan::select(
                DB::raw('DATE(tanggal) as tanggal'),
                DB::raw('SUM(total) as total'),
                DB::raw('COUNT(*) as jumlah_transaksi')
            )
            ->whereBetween('tanggal', [now()->subDays(6), now()])
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->orderBy('tanggal', 'asc')
            ->get();

        // Format data untuk Chart.js
        $chartLabels = $penjualanPerHari->map(function ($item) {
            return Carbon::parse($item->tanggal)->translatedFormat('d M');
        });

        $chartData = $penjualanPerHari->pluck('total');

        // 📊 Transaksi Terbaru (5 terakhir)
        $transaksiTerbaru = Penjualan::with('pelanggan')
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get();

        // 📊 Top Pelanggan (5 terbanyak transaksi)
        $topPelanggan = Pelanggan::withCount('penjualans')
            ->orderBy('penjualans_count', 'desc')
            ->limit(5)
            ->get();

        // 📊 Penjualan Belum Dibayar
        $penjualanBelumBayar = Penjualan::where('status_bayar', 'belum_lunas')
            ->orWhere('status_bayar', 'sebagian')
            ->count();

        return view('auth.dashboard', compact(
            'totalPenjualanHariIni',
            'totalPenjualanBulanIni',
            'jumlahTransaksiHariIni',
            'jumlahPelanggan',
            'jumlahSupplier',
            'totalPembayaranHariIni',
            'statusPembayaran',
            'chartLabels',
            'chartData',
            'transaksiTerbaru',
            'topPelanggan',
            'penjualanBelumBayar'
        ));
    }

    /**
     * 🔀 Redirect ke halaman pertama yang bisa diakses user
     */
    private function redirectToFirstAccessiblePage()
    {
        $user = Auth::user();

        // 📋 Urutan prioritas halaman yang akan dicek
        $accessiblePages = [
            // KASIR (prioritas tertinggi untuk kasir)
            ['permission' => 'penjualan_cepat.view', 'route' => 'penjualan-cepat.index', 'name' => 'Penjualan Cepat'],
            ['permission' => 'pembayaran.view', 'route' => 'pembayaran.index', 'name' => 'Pembayaran'],
            ['permission' => 'tagihan_penjualan.view', 'route' => 'tagihan-penjualan.index', 'name' => 'Tagihan Penjualan'],
            
            // PENJUALAN
            ['permission' => 'penjualan.view', 'route' => 'penjualan.index', 'name' => 'Penjualan'],
            ['permission' => 'pengiriman.view', 'route' => 'pengiriman.index', 'name' => 'Pengiriman'],
            ['permission' => 'retur_penjualan.view', 'route' => 'retur-penjualan.index', 'name' => 'Retur Penjualan'],
            
            // PEMBELIAN
            ['permission' => 'pembelian.view', 'route' => 'pembelian.index', 'name' => 'Pembelian'],
            ['permission' => 'tagihan_pembelian.view', 'route' => 'tagihan-pembelian.index', 'name' => 'Tagihan Pembelian'],
            ['permission' => 'retur_pembelian.view', 'route' => 'retur-pembelian.index', 'name' => 'Retur Pembelian'],
            
            // MANAJEMEN TOKO
            ['permission' => 'gudang.view', 'route' => 'gudang.index', 'name' => 'Gudang'],
            ['permission' => 'supplier.view', 'route' => 'supplier.index', 'name' => 'Supplier'],
            ['permission' => 'items.view', 'route' => 'items.index', 'name' => 'Items'],
            ['permission' => 'kategori_items.view', 'route' => 'items.categories.index', 'name' => 'Kategori Items'],
            ['permission' => 'pelanggan.view', 'route' => 'pelanggan.index', 'name' => 'Pelanggan'],
            ['permission' => 'mutasi_stok.view', 'route' => 'mutasi-stok.index', 'name' => 'Mutasi Stok'],
            ['permission' => 'produksi.view', 'route' => 'produksi.index', 'name' => 'Produksi'],
            
            // KEUANGAN
            ['permission' => 'cashflows.view', 'route' => 'kas-keuangan.index', 'name' => 'Kas Keuangan'],
            ['permission' => 'payrolls.view', 'route' => 'gaji-karyawan.index', 'name' => 'Gaji Karyawan'],
            
            // MANAJEMEN PENGGUNA
            ['permission' => 'roles.view', 'route' => 'roles.index', 'name' => 'Role'],
            ['permission' => 'users.view', 'route' => 'users.index', 'name' => 'Users'],
            ['permission' => 'activity_logs.view', 'route' => 'log-activity.index', 'name' => 'Log Aktivitas'],
        ];

        // 🔍 Cari halaman pertama yang bisa diakses
        foreach ($accessiblePages as $page) {
            if ($user->can($page['permission'])) {
                return redirect()->route($page['route'])
                    ->with('info', 'Anda tidak memiliki akses ke Dashboard. Dialihkan ke halaman ' . $page['name'] . '.');
            }
        }

        // 🚨 Jika tidak ada akses sama sekali, redirect ke profil
        return redirect()->route('profil.index')
            ->with('warning', 'Anda tidak memiliki akses ke fitur apapun. Silakan hubungi administrator untuk pengaturan hak akses.');
    }
}