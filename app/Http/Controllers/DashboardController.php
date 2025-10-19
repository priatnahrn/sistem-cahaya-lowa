<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Models\Pelanggan;
use App\Models\Supplier;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
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
}