@extends('layouts.app')

@section('title', 'Detail Tagihan Penjualan')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TOAST --}}
    <div x-data class="fixed top-6 right-6 space-y-3 z-50 w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
                <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
                <button class="ml-auto" @click="show=false"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <div class="space-y-6">

        {{-- Breadcrumb --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('tagihan-penjualan.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>

            {{-- ✅ Tombol Bayar via Pembayaran --}}
            @if (!$tagihan->is_lunas)
                <a href="{{ route('pembayaran.index') }}?penjualan={{ $tagihan->penjualan->no_faktur }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#344579] text-white rounded-lg hover:bg-[#2e3e6a] transition">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Bayar via Pembayaran</span>
                </a>
            @endif
        </div>

        {{-- Info Tagihan Card --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Informasi Tagihan</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Tagihan</label>
                    <input type="text" value="{{ $tagihan->no_tagihan }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" value="{{ $tagihan->penjualan->no_faktur }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <input type="text" value="{{ $tagihan->penjualan->pelanggan->nama_pelanggan ?? '-' }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Total Tagihan</label>
                    <input type="text" value="Rp {{ number_format($tagihan->total, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 font-semibold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sudah Dibayar</label>
                    <input type="text" value="Rp {{ number_format($tagihan->jumlah_bayar, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-green-700 font-semibold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sisa Tagihan</label>
                    <input type="text" value="Rp {{ number_format($tagihan->sisa, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-red-600 font-bold">
                </div>
            </div>

            {{-- INFO REKENING PELANGGAN --}}
            @if (
                $tagihan->penjualan->pelanggan &&
                    ($tagihan->penjualan->pelanggan->nama_bank || $tagihan->penjualan->pelanggan->nomor_rekening))
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-4 border border-indigo-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center">
                                <i class="fa-solid fa-building-columns text-white"></i>
                            </div>
                            <h4 class="font-semibold text-slate-800">Informasi Rekening Pelanggan</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @if ($tagihan->penjualan->pelanggan->nama_bank)
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Bank</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $tagihan->penjualan->pelanggan->nama_bank }}</p>
                                </div>
                            @endif
                            @if ($tagihan->penjualan->pelanggan->nomor_rekening)
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nomor Rekening</label>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-800 font-mono">
                                            {{ $tagihan->penjualan->pelanggan->nomor_rekening }}</p>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $tagihan->penjualan->pelanggan->nomor_rekening }}'); alert('Nomor rekening disalin!')"
                                            class="text-indigo-600 hover:text-indigo-800 transition text-xs">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Atas Nama</label>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $tagihan->penjualan->pelanggan->nama_pelanggan }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Daftar Item Penjualan --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-700">Detail Item Penjualan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 font-medium">No.</th>
                            <th class="px-4 py-3 font-medium">Nama Item</th>
                            <th class="px-4 py-3 font-medium">Gudang</th>
                            <th class="px-4 py-3 font-medium">Satuan</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                            <th class="px-4 py-3 text-right font-medium">Harga</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihan->penjualan->items as $index => $item)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-slate-600">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $item->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->gudang->nama_gudang ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->satuan->nama_satuan ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">Rp
                                    {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">Rp
                                    {{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-right font-semibold text-slate-700">Total:</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900">Rp
                                {{ number_format($tagihan->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Riwayat Pembayaran --}}
        @if ($tagihan->catatan)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-[#344579]"></i>
                        Riwayat Pembayaran
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-3">
                        @foreach (explode("\n", $tagihan->catatan) as $index => $catatan)
                            @if (trim($catatan))
                                <div
                                    class="flex gap-3 pb-3 items-start {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-full bg-[#344579]/10 flex items-center justify-center">
                                        <span class="text-xs font-semibold text-[#344579]">{{ $index + 1 }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-2">
                                        <p class="text-sm text-slate-700 break-words">{{ trim($catatan) }}</p>
                                        @if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $catatan, $matches))
                                            <p class="text-xs text-slate-500 mt-1">
                                                <i class="fa-solid fa-calendar-days mr-1"></i>
                                                {{ $matches[0] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>

@endsection
