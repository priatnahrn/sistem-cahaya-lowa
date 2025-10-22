@extends('layouts.app')

@section('title', 'Kas Keuangan')

@section('content')
    <div class="container mx-auto px-4 py-6">

        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-wallet text-[#344579]"></i>
                    Kas Keuangan
                </h1>
                <p class="text-sm text-gray-600 mt-1">Kelola kas masuk dan keluar</p>
            </div>

            @can('cashflows.create')
                <div class="mt-4 md:mt-0">
                    <button onclick="openTambahKasModal()"
                        class="px-4 py-2 bg-[#344579] hover:bg-[#2e3e6a] text-white rounded-lg transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Kas</span>
                    </button>
                </div>
            @endcan
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            @can('users.view')
                {{-- Super Admin View --}}
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-blue-100 text-sm">Total Kas Keseluruhan</p>
                        <i class="fa-solid fa-building-columns text-2xl text-blue-200"></i>
                    </div>
                    <p class="text-3xl font-bold">Rp {{ number_format($totalKeseluruhan ?? 0, 0, ',', '.') }}</p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-green-100 text-sm">Jumlah Kasir</p>
                        <i class="fa-solid fa-users text-2xl text-green-200"></i>
                    </div>
                    <p class="text-3xl font-bold">{{ $totalPerKasir->count() ?? 0 }} Kasir</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-purple-100 text-sm">Total Transaksi</p>
                        <i class="fa-solid fa-receipt text-2xl text-purple-200"></i>
                    </div>
                    <p class="text-3xl font-bold">{{ $kasKeuangan->total() }}</p>
                </div>
            @else
                {{-- Kasir View --}}
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-blue-100 text-sm">Saldo Sistem (Total)</p>
                        <i class="fa-solid fa-wallet text-2xl text-blue-200"></i>
                    </div>
                    <p class="text-3xl font-bold">Rp {{ number_format($saldoSistem ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-blue-100 mt-2">Cash + Transfer + QRIS + Wallet</p>
                </div>

                <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-green-100 text-sm">Saldo Cash (Di Laci)</p>
                        <i class="fa-solid fa-money-bill-wave text-2xl text-green-200"></i>
                    </div>
                    <p class="text-3xl font-bold">Rp {{ number_format($saldoCash ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-green-100 mt-2">Hanya Tunai</p>
                </div>

                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-purple-100 text-sm">Selisih Hari Ini</p>
                        <i class="fa-solid fa-chart-line text-2xl text-purple-200"></i>
                    </div>
                    <p class="text-3xl font-bold">Rp
                        {{ number_format(($pemasukanHariIni ?? 0) - ($pengeluaranHariIni ?? 0), 0, ',', '.') }}</p>
                    <p class="text-xs text-purple-100 mt-2">Masuk: {{ number_format($pemasukanHariIni ?? 0, 0, ',', '.') }} |
                        Keluar: {{ number_format($pengeluaranHariIni ?? 0, 0, ',', '.') }}</p>
                </div>
            @endcan
        </div>

        {{-- Super Admin: Kas Per Kasir --}}
        @can('users.view')
            @if ($totalPerKasir && $totalPerKasir->count() > 0)
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-users text-[#344579]"></i>
                        Kas Per Kasir
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($totalPerKasir as $kasir)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 mb-2">
                                    <div
                                        class="w-10 h-10 rounded-full bg-[#344579] text-white flex items-center justify-center font-semibold">
                                        {{ substr($kasir->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $kasir->name ?? 'Unknown' }}</p>
                                        <p class="text-xs text-gray-500">{{ $kasir->email ?? '-' }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 pt-3 border-t border-gray-100">
                                    <p class="text-sm text-gray-600 mb-1">Total Kas</p>
                                    <p
                                        class="text-xl font-bold {{ $kasir->total_kas >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        Rp {{ number_format($kasir->total_kas ?? 0, 0, ',', '.') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endcan

        {{-- Filter --}}
        <div class="bg-white rounded-xl shadow-md p-4 mb-6">
            <form method="GET" action="{{ route('kas-keuangan.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#344579] focus:border-transparent">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Akhir</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#344579] focus:border-transparent">
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-[#344579] text-white rounded-lg hover:bg-[#2e3e6a] transition-colors">
                        <i class="fa-solid fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ route('kas-keuangan.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Tabel Kas --}}
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tanggal</th>
                            @can('users.view')
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Kasir</th>
                            @endcan
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Jenis</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Keterangan</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Nominal</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($kasKeuangan as $kas)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ $kas->created_at->format('d/m/Y H:i') }}
                                </td>
                                @can('users.view')
                                    <td class="px-4 py-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-full bg-[#344579] text-white flex items-center justify-center text-xs font-semibold">
                                                {{ substr($kas->user->name ?? 'U', 0, 1) }}
                                            </div>
                                            <span class="text-gray-700">{{ $kas->user->name ?? 'Unknown' }}</span>
                                        </div>
                                    </td>
                                @endcan
                                <td class="px-4 py-3">
                                    @if ($kas->jenis === 'masuk')
                                        <span
                                            class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full">
                                            <i class="fa-solid fa-arrow-up mr-1"></i>Pemasukan
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold text-red-700 bg-red-100 rounded-full">
                                            <i class="fa-solid fa-arrow-down mr-1"></i>Pengeluaran
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $kas->keterangan }}
                                    @if ($kas->pembayaran)
                                        <span class="text-xs text-gray-500 block mt-1">
                                            <i class="fa-solid fa-link"></i>
                                            {{ $kas->pembayaran->penjualan->no_faktur ?? '-' }}
                                        </span>
                                    @endif
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-right font-semibold {{ $kas->jenis === 'masuk' ? 'text-green-600' : 'text-red-600' }}">
                                    Rp {{ number_format($kas->nominal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        @if (!$kas->pembayarans_id)
                                            @can('cashflows.delete')
                                                <button onclick="deleteKas({{ $kas->id }})"
                                                    class="text-red-600 hover:text-red-800" title="Hapus">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            @endcan
                                        @else
                                            <span class="text-xs text-gray-400 italic">Otomatis</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->can('users.view') ? '6' : '5' }}"
                                    class="px-4 py-8 text-center text-gray-500">
                                    <i class="fa-solid fa-inbox text-4xl mb-2 text-gray-300"></i>
                                    <p>Belum ada data kas keuangan</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-gray-200">
                {{ $kasKeuangan->links() }}
            </div>
        </div>
    </div>

    {{-- Modal Tambah Kas --}}
    @can('cashflows.create')
        <div id="tambahKasModal"
            class="hidden fixed inset-0 bg-black/50 bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Tambah Kas Manual</h3>
                    <button onclick="closeTambahKasModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-times text-xl"></i>
                    </button>
                </div>
                <form id="tambahKasForm" class="px-6 py-4">
                    @csrf

                    {{-- Pilih Jenis --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Transaksi <span
                                class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label
                                class="relative flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-green-500 transition-colors has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                <input type="radio" name="jenis" value="masuk" required class="sr-only peer">
                                <div class="text-center">
                                    <i class="fa-solid fa-arrow-down text-2xl text-green-600 mb-2"></i>
                                    <p class="font-semibold text-gray-800">Pemasukan</p>
                                    <p class="text-xs text-gray-500 mt-1">Uang masuk</p>
                                </div>
                            </label>

                            <label
                                class="relative flex items-center justify-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-red-500 transition-colors has-[:checked]:border-red-500 has-[:checked]:bg-red-50">
                                <input type="radio" name="jenis" value="keluar" required class="sr-only peer">
                                <div class="text-center">
                                    <i class="fa-solid fa-arrow-up text-2xl text-red-600 mb-2"></i>
                                    <p class="font-semibold text-gray-800">Pengeluaran</p>
                                    <p class="text-xs text-gray-500 mt-1">Uang keluar</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Nominal --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nominal <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                            <input type="number" name="nominal" id="nominalKas" required min="0" step="1"
                                class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#344579] focus:border-transparent"
                                placeholder="0">
                        </div>
                    </div>

                    {{-- Keterangan --}}
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Keterangan <span
                                class="text-red-500">*</span></label>
                        <textarea name="keterangan" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#344579] focus:border-transparent resize-none"
                            placeholder="Contoh: Ambil uang untuk belanja, Pemasukan dari sumber lain, dll."></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="fa-solid fa-info-circle"></i> Jelaskan sumber atau tujuan kas secara detail
                        </p>
                    </div>

                    {{-- Tombol Aksi --}}
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeTambahKasModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            <i class="fa-solid fa-times mr-2"></i>Batal
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-[#344579] text-white rounded-lg hover:bg-[#2e3e6a] transition-colors">
                            <i class="fa-solid fa-check mr-2"></i>Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Buka modal tambah kas
            function openTambahKasModal() {
                document.getElementById('tambahKasModal').classList.remove('hidden');
            }

            // Tutup modal tambah kas
            function closeTambahKasModal() {
                document.getElementById('tambahKasModal').classList.add('hidden');
                document.getElementById('tambahKasForm').reset();
            }

            // Submit form tambah kas
            document.getElementById('tambahKasForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const jenis = formData.get('jenis');
                const nominal = formData.get('nominal');
                const keterangan = formData.get('keterangan');

                // Validasi
                if (!jenis || !nominal || !keterangan) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Perhatian!',
                        text: 'Mohon lengkapi semua field yang wajib diisi.',
                        confirmButtonColor: '#344579'
                    });
                    return;
                }

                try {
                    const response = await fetch('{{ route('kas-keuangan.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            jenis: jenis,
                            nominal: parseFloat(nominal),
                            keterangan: keterangan
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: data.message,
                            confirmButtonColor: '#344579'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'Gagal menyimpan kas');
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: error.message || 'Terjadi kesalahan saat menyimpan kas.',
                        confirmButtonColor: '#344579'
                    });
                }
            });

            // Hapus kas
            async function deleteKas(id) {
                const result = await Swal.fire({
                    title: 'Hapus Kas?',
                    text: 'Data kas yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                });

                if (result.isConfirmed) {
                    try {
                        const response = await fetch(`/kas-keuangan/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();

                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: data.message,
                                confirmButtonColor: '#344579'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            throw new Error(data.message);
                        }
                    } catch (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: error.message || 'Terjadi kesalahan saat menghapus kas.',
                            confirmButtonColor: '#344579'
                        });
                    }
                }
            }

            // Tutup modal saat klik di luar
            document.getElementById('tambahKasModal')?.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeTambahKasModal();
                }
            });

            // Tutup modal dengan tombol ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeTambahKasModal();
                }
            });
        </script>
    @endpush
@endsection
