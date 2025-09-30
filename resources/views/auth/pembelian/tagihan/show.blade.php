@extends('layouts.app')

@section('title', 'Detail Tagihan Pembelian')

@section('content')
    {{-- Toast Container --}}
    <div x-data="{ toasts: [] }" x-init="$watch('toasts', () => { setTimeout(() => toasts.shift(), 4000) })"
         class="fixed top-6 right-6 space-y-3 z-50 w-80">
        <template x-for="(t, i) in toasts" :key="i">
            <div x-transition class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                 :class="t.type === 'error'
                        ? 'bg-rose-50 text-rose-700 border border-rose-200'
                        : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

    <div x-data="tagihanShowPage()" x-init="init()" class="space-y-8">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('tagihan.pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Tagihan Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    {{ $tagihan->no_tagihan }}
                </span>
            </div>
        </div>

        {{-- Info Tagihan --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Tagihan</label>
                    <input type="text" value="{{ $tagihan->no_tagihan }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" value="{{ $tagihan->pembelian->no_faktur }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <input type="text" value="{{ $tagihan->pembelian->supplier->nama ?? '-' }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="text" value="{{ $tagihan->tanggal->format('d/m/Y H:i') }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Jumlah Bayar</label>
                    <input type="text" value="Rp {{ number_format($tagihan->jumlah_bayar,0,',','.') }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-green-700 font-semibold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sisa</label>
                    <input type="text" value="Rp {{ number_format($tagihan->sisa,0,',','.') }}" readonly
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-red-600 font-semibold">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Total</label>
                <input type="text" value="Rp {{ number_format($tagihan->total,0,',','.') }}" readonly
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 font-bold">
            </div>
        </div>

        {{-- Daftar Item --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-700">Detail Pembelian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 text-left font-medium">Item</th>
                            <th class="px-4 py-3 text-left font-medium">Gudang</th>
                            <th class="px-4 py-3 text-left font-medium">Satuan</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                            <th class="px-4 py-3 text-right font-medium">Harga</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tagihan->pembelian->items as $item)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3">{{ $item->item->nama ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item->gudang->nama ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $item->satuan->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">{{ $item->jumlah }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($item->harga_beli,0,',','.') }}</td>
                                <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($item->total,0,',','.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('tagihan.pembelian.index') }}"
               class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                Kembali
            </a>
            @if($tagihan->sisa > 0)
                <a href="{{ route('tagihan.pembelian.edit', $tagihan->id) }}"
                   class="px-5 py-2.5 rounded-lg bg-[#344579] hover:bg-[#2e3e6a] text-white font-medium">
                    <i class="fa-solid fa-pen mr-2"></i> Edit Tagihan
                </a>
            @endif
        </div>
    </div>

    <script>
        function tagihanShowPage() {
            return {
                toasts: [],
                init() {}
            }
        }
    </script>
@endsection
