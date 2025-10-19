@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    {{-- Welcome Section --}}
    <div class="bg-gradient-to-r from-[#344579] to-[#4f6699] text-white rounded-2xl p-6 mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold mb-1">Selamat Datang, {{ Auth::user()->name }} 🎉</h1>
            <p class="text-sm opacity-90">Semoga harimu menyenangkan. Berikut ringkasan data tokomu hari ini.</p>
        </div>
        <div class="text-right">
            <p class="text-sm opacity-80">Hari ini</p>
            <p class="text-lg font-semibold">{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</p>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        {{-- Total Penjualan Hari Ini --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Penjualan</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">Rp {{ number_format($totalPenjualanHariIni, 0, ',', '.') }}</h2>
                    <p class="text-xs text-slate-400 mt-1">{{ $jumlahTransaksiHariIni }} transaksi</p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <i class="fa-solid fa-coins text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Total Pembayaran --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Total Pembayaran</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">Rp {{ number_format($totalPembayaranHariIni, 0, ',', '.') }}</h2>
                    <p class="text-xs text-slate-400 mt-1">Hari ini</p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-sky-100 text-sky-600">
                    <i class="fa-solid fa-money-bill-wave text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Penjualan Belum Dibayar --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Belum Dibayar</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">{{ $penjualanBelumBayar }}</h2>
                    <p class="text-xs text-slate-400 mt-1">Transaksi</p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-orange-100 text-orange-600">
                    <i class="fa-solid fa-hourglass-end text-lg"></i>
                </div>
            </div>
        </div>

        {{-- Jumlah Pelanggan --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Jumlah Pelanggan</p>
                    <h2 class="text-2xl font-bold text-slate-700 mt-1">{{ $jumlahPelanggan }}</h2>
                    <p class="text-xs text-slate-400 mt-1">{{ $jumlahSupplier }} Supplier</p>
                </div>
                <div class="w-12 h-12 flex items-center justify-center rounded-full bg-pink-100 text-pink-600">
                    <i class="fa-solid fa-user-group text-lg"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafik & Table --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Grafik Penjualan 7 Hari --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 lg:col-span-2 hover:border-slate-300 transition">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Grafik Penjualan 7 Hari Terakhir</h3>
            <div class="h-64">
                <canvas id="penjualanChart"></canvas>
            </div>
        </div>

        {{-- Transaksi Terbaru --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Transaksi Terbaru</h3>
            <div class="space-y-3">
                @forelse($transaksiTerbaru as $transaksi)
                    <div class="border-b border-slate-100 pb-3 last:border-b-0">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-slate-700">{{ $transaksi->no_faktur }}</p>
                                <p class="text-xs text-slate-500">{{ $transaksi->pelanggan->nama_pelanggan ?? 'CUSTOMER' }}</p>
                                <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($transaksi->tanggal)->translatedFormat('d M Y H:i') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-700">Rp {{ number_format($transaksi->total, 0, ',', '.') }}</p>
                                <span class="text-xs px-2 py-1 rounded-full 
                                    @if($transaksi->status_bayar == 'lunas') bg-emerald-100 text-emerald-700
                                    @elseif($transaksi->status_bayar == 'sebagian') bg-amber-100 text-amber-700
                                    @else bg-red-100 text-red-700 @endif">
                                    {{ ucfirst($transaksi->status_bayar) }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-4">Belum ada transaksi</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Status Pembayaran & Top Pelanggan --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Status Pembayaran --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Status Pembayaran</h3>
            <div class="space-y-3">
                @foreach($statusPembayaran as $status)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full 
                                @if($status->status_bayar == 'lunas') bg-emerald-500
                                @elseif($status->status_bayar == 'sebagian') bg-amber-500
                                @else bg-red-500 @endif"></div>
                            <p class="text-sm text-slate-600">{{ ucfirst($status->status_bayar) }}</p>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">{{ $status->total }} transaksi</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Top Pelanggan --}}
        <div class="bg-white border border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Top 5 Pelanggan</h3>
            <div class="space-y-3">
                @forelse($topPelanggan as $pelanggan)
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100 last:border-b-0">
                        <div>
                            <p class="text-sm font-medium text-slate-700">{{ $pelanggan->nama_pelanggan }}</p>
                            <p class="text-xs text-slate-500">{{ $pelanggan->kontak }}</p>
                        </div>
                        <span class="text-sm font-semibold text-emerald-600">{{ $pelanggan->penjualans_count }} transaksi</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-4">Belum ada pelanggan</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Chart.js Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        const ctx = document.getElementById('penjualanChart').getContext('2d');
        const penjualanChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Total Penjualan',
                    data: @json($chartData),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
                    }
                }
            }
        });
    </script>
@endsection