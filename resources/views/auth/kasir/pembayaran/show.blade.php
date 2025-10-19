@extends('layouts.app')

@section('title', 'Detail Pembayaran')

@section('content')
    <div class="space-y-6">

        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('pembayaran.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- Form Utama --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Nomor Transaksi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Transaksi</label>
                    <input type="text" value="{{ $pembayaran->penjualan->no_faktur ?? '-' }}" readonly
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-600" />
                </div>

                {{-- Tanggal Pembayaran --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Pembayaran</label>
                    <input type="text"
                        value="{{ $pembayaran->tanggal->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA" readonly
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>

                {{-- Metode Pembayaran --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Metode Pembayaran</label>
                    <input type="text"
                        value="{{ match ($pembayaran->method) {
                            'cash' => 'Tunai',
                            'transfer' => 'Transfer',
                            'qris' => 'QRIS',
                            'wallet' => 'E-Wallet',
                            default => '-',
                        } }}"
                        readonly class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>
            </div>

            {{-- Keterangan --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Keterangan</label>
                <input type="text" value="{{ $pembayaran->keterangan ?? '-' }}" readonly
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
            </div>

            {{-- Informasi Penjualan --}}
            <div class="bg-slate-50 border border-slate-200 rounded-xl px-6 py-5 space-y-4">
                <h3 class="font-semibold text-slate-800 border-b border-slate-200 pb-2">Informasi Penjualan</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-700">
                    <p>
                        <span class="font-medium inline-block w-40">No Faktur:</span>
                        {{ $pembayaran->penjualan->no_faktur ?? '-' }}
                    </p>
                    <p>
                        <span class="font-medium inline-block w-40">Tanggal Penjualan:</span>
                        {{ $pembayaran->penjualan->tanggal->timezone('Asia/Makassar')->format('d-m-Y H:i') }} WITA
                    </p>
                    <p>
                        <span class="font-medium inline-block w-40">Pelanggan:</span>
                        {{ $pembayaran->penjualan->pelanggan->nama_pelanggan ?? 'Customer' }}
                    </p>
                    <p>
                        <span class="font-medium inline-block w-40">Kasir:</span>
                        {{ $pembayaran->penjualan->createdBy->name ?? 'Admin' }}
                    </p>
                </div>
            </div>

            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">Daftar Item Penjualan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-center">#</th>
                                <th class="px-4 py-3 text-left">Nama Item</th>
                                <th class="px-4 py-3 text-center">Jumlah</th>
                                <th class="px-4 py-3 text-center">Satuan</th>
                                <th class="px-4 py-3 text-right">Harga</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pembayaran->penjualan->items as $idx => $item)
                                <tr class="hover:bg-slate-50 border-b border-slate-100">
                                    <td class="px-4 py-3 text-center">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $item->item->nama_item ?? '-' }}</div>
                                        <div class="text-xs text-slate-500">{{ $item->item->kode_item ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-center">{{ number_format($item->jumlah, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-center">{{ $item->satuan->nama_satuan ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-800">
                                        Rp {{ number_format($item->jumlah * $item->harga, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                        <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                                        <p>Tidak ada item</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Ringkasan & Aksi --}}
            <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
                <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Total Penjualan</div>
                        <div class="font-normal text-slate-700">
                            Rp {{ number_format($pembayaran->penjualan->total ?? 0, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Biaya Transportasi</div>
                        <div class="text-slate-700 font-medium">
                            Rp {{ number_format($pembayaran->penjualan->biaya_transport ?? 0, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Total Bayar</div>
                        <div class="text-green-700 font-medium">
                            Rp {{ number_format($pembayaran->jumlah_bayar ?? 0, 0, ',', '.') }}
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4 mt-4"></div>

                    @php
                        $totalTagihan =
                            ($pembayaran->penjualan->total ?? 0) + ($pembayaran->penjualan->biaya_transport ?? 0);
                        $kembalian = ($pembayaran->jumlah_bayar ?? 0) - $totalTagihan;
                    @endphp

                    @if ($kembalian > 0)
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-slate-700 font-bold text-lg">Kembalian</div>
                            <div class="text-blue-600 text-xl font-bold tracking-wide">
                                Rp {{ number_format($kembalian, 0, ',', '.') }}
                            </div>
                        </div>
                    @endif


                    <div class="flex justify-between items-center">
                        <div class="text-slate-700 font-bold text-lg">Sisa Tagihan</div>
                        <div class="text-rose-600 text-xl font-bold tracking-wide">
                            Rp {{ number_format($pembayaran->sisa ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            </div>


        </div>

    </div>
@endsection
