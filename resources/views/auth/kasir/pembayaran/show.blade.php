@extends('layouts.app')

@section('title', 'Edit Pembayaran')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<div x-data="pembayaranEditPage()" x-init="init()" class="space-y-6">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('pembayaran.index') }}" class="text-slate-500 hover:underline text-sm">Pembayaran</a>
        <div class="text-sm text-slate-400">/</div>
        <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
            {{ $pembayaran->penjualan->no_faktur ?? 'Pembayaran' }}
        </span>
    </div>

    {{-- Form Utama --}}
    <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Nomor Transaksi --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Transaksi</label>
                <input type="text" x-model="form.kode_transaksi" readonly
                    class="w-full px-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-600" />
            </div>

            {{-- Tanggal Pembayaran --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Pembayaran</label>
                <input type="datetime-local" x-model="form.tanggal"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
            </div>

            {{-- Metode Pembayaran --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Metode Pembayaran</label>
                <select x-model="form.method"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                    <option value="qris">QRIS</option>
                    <option value="wallet">E-Wallet</option>
                </select>
            </div>
        </div>

        {{-- Keterangan --}}
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Keterangan (Opsional)</label>
            <input type="text" x-model="form.keterangan" placeholder="Catatan pembayaran..."
                class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
        </div>
    </div>

    {{-- Informasi Penjualan --}}
    <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4">
        <h3 class="font-semibold text-slate-800 border-b border-slate-200 pb-2">Informasi Penjualan</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-700">
            <p><span class="font-medium w-36 inline-block">No Faktur:</span> {{ $pembayaran->penjualan->no_faktur ?? '-' }}</p>
            <p><span class="font-medium w-36 inline-block">Tanggal Penjualan:</span>
                {{ optional($pembayaran->penjualan->tanggal)->timezone('Asia/Makassar')->format('d-m-Y H:i') }}
            </p>
            <p><span class="font-medium w-36 inline-block">Pelanggan:</span> {{ optional($pembayaran->penjualan->pelanggan)->nama_pelanggan ?? '-' }}</p>
            <p><span class="font-medium w-36 inline-block">Kasir:</span> {{ optional($pembayaran->penjualan->user)->name ?? '-' }}</p>
        </div>
    </div>

    {{-- Daftar Item --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
            <h3 class="font-semibold text-slate-800">Daftar Item Penjualan</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-center">#</th>
                        <th class="px-4 py-3">Nama Item</th>
                        <th class="px-4 py-3 text-center">Jumlah</th>
                        <th class="px-4 py-3 text-center">Satuan</th>
                        <th class="px-4 py-3 text-right">Harga</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pembayaran->penjualan->items as $idx => $item)
                        <tr class="hover:bg-slate-50 border-b border-slate-100">
                            <td class="px-4 py-3 text-center">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3">{{ $item->item->nama_item ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->jumlah }}</td>
                            <td class="px-4 py-3 text-center">{{ $item->satuan->nama_satuan ?? '-' }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($item->harga_jual, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">
                                Rp {{ number_format($item->jumlah * $item->harga_jual, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
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
                <div class="text-slate-600">Total Bayar</div>
                <div class="text-green-700 font-medium">
                    Rp <span x-text="formatRupiah(form.jumlah_bayar)"></span>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4 mt-4"></div>

            <div class="flex justify-between items-center mb-6">
                <div class="text-slate-700 font-bold text-lg">Sisa Tagihan</div>
                <div class="text-rose-600 text-xl font-bold tracking-wide">
                    Rp {{ number_format($pembayaran->sisa ?? 0, 0, ',', '.') }}
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('pembayaran.index') }}"
                    class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                    Kembali
                </a>
                <button @click="update()" type="button"
                    class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-medium text-white
                    bg-[#334976] hover:bg-[#2d3f6d] transition shadow-sm hover:shadow-md">
                    <i class="fa-solid fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function pembayaranEditPage() {
    return {
        form: {
            id: {{ $pembayaran->id }},
            kode_transaksi: {{ Js::from($pembayaran->penjualan->no_faktur ?? '-') }},
            tanggal: {{ Js::from($pembayaran->tanggal->format('Y-m-d\TH:i')) }},
            method: {{ Js::from($pembayaran->method) }},
            keterangan: {{ Js::from($pembayaran->keterangan ?? '') }},
            jumlah_bayar: {{ $pembayaran->jumlah_bayar }},
        },

        formatRupiah(n) {
            return new Intl.NumberFormat('id-ID').format(n || 0);
        },

        async update() {
            const res = await fetch(`/pembayaran/${this.form.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.form),
            });

            if (res.ok) {
                this.showToast('Perubahan disimpan.', 'success');
            } else {
                this.showToast('Gagal menyimpan perubahan.', 'error');
            }
        },

        showToast(msg, type) {
            const el = document.createElement('div');
            el.className = `fixed top-6 right-6 px-4 py-3 rounded-md border shadow text-sm z-50
                ${type === 'success' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'}`;
            el.innerHTML = `<i class="fa-solid ${type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'} mr-2"></i>${msg}`;
            document.body.appendChild(el);
            setTimeout(() => el.remove(), 3500);
        },

        init() {},
    }
}
</script>
@endsection
