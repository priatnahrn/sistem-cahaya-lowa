@extends('layouts.app')

@section('title', 'Detail Produksi')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div x-data="produksiShowPage()" x-init="init()" class="space-y-6">

        {{-- 🧭 Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('produksi.index') }}" class="text-slate-500 hover:underline text-sm">Produksi</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                {{ $produksi->no_produksi }}
            </span>
        </div>

        {{-- 🧩 Informasi Produksi --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Nomor Produksi</label>
                        <div class="font-semibold text-slate-800">{{ $produksi->no_produksi }}</div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Pelanggan</label>
                        <div class="font-semibold text-green-700">
                            {{ $produksi->penjualan->pelanggan->nama_pelanggan ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Deskripsi Produksi</label>
                        <div class="text-slate-700">{{ $produksi->deskripsi ?? '-' }}</div>
                    </div>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Tanggal Produksi</label>
                        <div class="font-semibold text-slate-800">
                            {{ \Carbon\Carbon::parse($produksi->penjualan->tanggal)->timezone('Asia/Makassar')->format('d-m-Y H:i') }}
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-1">Status Produksi</label>
                        <span class="inline-flex items-center gap-2 text-sm font-medium px-3 py-1 rounded-full"
                            @class([
                                'bg-orange-50 text-orange-700 border border-orange-200' =>
                                    $produksi->status === 'pending',
                                'bg-blue-50 text-blue-700 border border-blue-200' =>
                                    $produksi->status === 'in_progress',
                                'bg-green-50 text-green-700 border border-green-200' =>
                                    $produksi->status === 'completed',
                            ])>
                            <span class="w-2 h-2 rounded-full" @class([
                                'bg-orange-500' => $produksi->status === 'pending',
                                'bg-blue-500' => $produksi->status === 'in_progress',
                                'bg-green-500' => $produksi->status === 'completed',
                            ])></span>
                            {{ ucfirst(str_replace('_', ' ', $produksi->status)) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🧾 Daftar Item Produksi --}}
        {{-- 🧾 Daftar Item Produksi --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800">Daftar Item Produksi</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 text-center w-12">#</th>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-center w-36">Jumlah Dibutuhkan</th>
                            <th class="px-4 py-3 text-center w-36">Satuan</th>
                            <th class="px-4 py-3 text-left">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($produksi->items as $idx => $item)
                            <tr class="hover:bg-slate-50 border-b border-slate-100 text-slate-700">
                                <td class="text-center px-4 py-3">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ $item->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($item->jumlah_dibutuhkan) }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->itemPenjualan->satuan->nama_satuan }}</td>
                               
                                <td class="px-4 py-3 text-slate-600 ">Rp {{ number_format($item->itemPenjualan->total ?? 0, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-slate-500 italic">
                                    Belum ada item produksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        {{-- 🎯 Tombol Aksi --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('produksi.index') }}"
                class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                <i class="fa-solid fa-arrow-left mr-1.5"></i> Kembali
            </a>

            @if ($produksi->status === 'pending')
                <button @click="updateStatus('{{ $produksi->id }}', 'in_progress')"
                    class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                    <i class="fa-solid fa-industry mr-1.5"></i> Tandai Sedang Diproduksi
                </button>
            @elseif ($produksi->status === 'in_progress')
                <button @click="updateStatus('{{ $produksi->id }}', 'completed')"
                    class="px-5 py-2.5 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">
                    <i class="fa-solid fa-circle-check mr-1.5"></i> Tandai Selesai
                </button>
            @endif
        </div>
    </div>

    {{-- 🧠 Script --}}
    <script>
        function produksiShowPage() {
            return {
                async updateStatus(id, status) {
                    const token = document.querySelector('meta[name=csrf-token]').content;
                    if (!confirm('Ubah status produksi menjadi "' + status.replace('_', ' ') + '"?')) return;

                    try {
                        const res = await fetch(`/produksi/${id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                status
                            })
                        });
                        const result = await res.json();


                        if (!res.ok) throw new Error(result.message || 'Gagal memperbarui status.');
                        alert(result.message || 'Status berhasil diperbarui.');
                        window.location.reload();
                    } catch (err) {
                        console.error(err);
                        alert('Terjadi kesalahan saat memperbarui status.');
                    }
                },
                init() {
                    console.log('🧩 Detail Produksi Loaded');
                },


            }
        }
    </script>

@endsection
