@extends('layouts.app')

@section('title', 'Detail Produksi')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="produksiShowPage()" x-init="init()" class="space-y-6">

        {{-- 🔔 Toast Notification --}}
        <div x-show="showNotif" x-transition x-cloak class="fixed top-5 right-5 z-50">
            <div :class="{
                'bg-green-500': notifType === 'success',
                'bg-red-500': notifType === 'error',
                'bg-blue-500': notifType === 'info'
            }"
                class="text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 min-w-[250px]">
                <template x-if="notifType === 'success'">
                    <i class="fa-solid fa-circle-check"></i>
                </template>
                <template x-if="notifType === 'error'">
                    <i class="fa-solid fa-circle-xmark"></i>
                </template>
                <template x-if="notifType === 'info'">
                    <i class="fa-solid fa-circle-info"></i>
                </template>
                <span x-text="notifMessage"></span>
            </div>
        </div>

        {{-- 🗨️ Modal Konfirmasi --}}
        <div x-show="showModal" x-cloak @click.self="showModal = false"
            class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 px-4 min-h-screen">
            <div @click.away="showModal = false" x-transition
                class="bg-white rounded-xl shadow-2xl max-w-md w-full overflow-hidden">
                {{-- Header --}}
                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                    <h3 class="text-lg font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-circle-question"></i>
                        <span>Konfirmasi Perubahan Status</span>
                    </h3>
                </div>

                {{-- Body --}}
                <div class="px-6 py-5">
                    <p class="text-slate-700" x-text="modalMessage"></p>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-slate-50 flex justify-end gap-3">
                    <button @click="showModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition font-medium">
                        <i class="fa-solid fa-xmark mr-1"></i> Batal
                    </button>
                    <button @click="confirmUpdate()"
                        class="px-4 py-2 rounded-lg bg-[#334976] text-white hover:bg-[#2d3f6d] transition font-medium">
                        <i class="fa-solid fa-check mr-1"></i> Ya, Ubah Status
                    </button>
                </div>
            </div>
        </div>

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('produksi.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- 🧩 Informasi Produksi --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5">
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
                        <div>
                            @if ($produksi->status === 'pending')
                                <span
                                    class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-gradient-to-r from-orange-50 to-orange-100 text-orange-700 border border-orange-300 shadow-sm">
                                    <i class="fa-solid fa-clock"></i>
                                    <span>Menunggu</span>
                                </span>
                            @elseif($produksi->status === 'in_progress')
                                <span
                                    class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-gradient-to-r from-blue-50 to-blue-100 text-blue-700 border border-blue-300 shadow-sm">
                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                    <span>Sedang Diproduksi</span>
                                </span>
                            @elseif($produksi->status === 'completed')
                                <span
                                    class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-gradient-to-r from-green-50 to-green-100 text-green-700 border border-green-300 shadow-sm">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Selesai</span>
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-2 text-sm font-semibold px-4 py-2 rounded-lg bg-gradient-to-r from-slate-50 to-slate-100 text-slate-700 border border-slate-300">
                                    <i class="fa-solid fa-question-circle"></i>
                                    <span>{{ ucfirst(str_replace('_', ' ', $produksi->status)) }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- 🧾 Daftar Item Produksi --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800">Daftar Item Produksi</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 text-center w-12">No.</th>
                            <th class="px-4 py-3 text-left">Item</th>
                            <th class="px-4 py-3 text-center w-36">Jumlah Dibutuhkan</th>
                            <th class="px-4 py-3 text-center w-36">Satuan</th>
                            <th class="px-4 py-3 text-left">Total</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            // Filter hanya item dengan kategori spandek
                            $filteredItems = $produksi->itemPenjualan
                                ->filter(function ($item) {
                                    $kategori =
                                        $item->item->kategori->nama_kategori ??
                                        ($item->item->kategoriItem->nama_kategori ?? null);
                                    return $kategori && strtolower($kategori) === 'spandek';
                                })
                                ->values();
                        @endphp

                        @forelse ($filteredItems as $idx => $item)
                            <tr class="hover:bg-slate-50 border-b border-slate-100 text-slate-700">
                                <td class="text-center px-4 py-3">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3 font-medium">{{ $item->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">{{ number_format($item->jumlah) }}</td>
                                <td class="px-4 py-3 text-center">{{ $item->satuan->nama_satuan }}</td>
                                <td class="px-4 py-3 text-slate-600">Rp {{ number_format($item->total ?? 0, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-slate-500 italic">
                                    Belum ada item produksi spandek.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- 🎯 Tombol Aksi --}}
        <div class="flex justify-end gap-3">


            @can('produksi.update')
                @if ($produksi->status === 'pending')
                    <button @click="showConfirmModal('{{ $produksi->id }}', 'in_progress')"
                        class="px-5 py-2.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition font-medium">
                        <i class="fa-solid fa-industry mr-1.5"></i> Tandai Sedang Diproduksi
                    </button>
                @elseif ($produksi->status === 'in_progress')
                    <button @click="showConfirmModal('{{ $produksi->id }}', 'completed')"
                        class="px-5 py-2.5 rounded-lg bg-[#334976] text-white hover:bg-[#2d3f6d] transition font-medium">
                        <i class="fa-solid fa-circle-check mr-1.5"></i> Tandai Selesai
                    </button>
                @endif
            @endcan
        </div>
    </div>

    {{-- 🧠 Script --}}
    <script>
        function produksiShowPage() {
            return {
                showModal: false,
                showNotif: false,
                notifMessage: '',
                notifType: '',
                modalMessage: '',
                pendingId: null,
                pendingStatus: null,

                statusLabels: {
                    'in_progress': 'Sedang Diproduksi',
                    'completed': 'Selesai'
                },

                showConfirmModal(id, status) {
                    this.pendingId = id;
                    this.pendingStatus = status;
                    this.modalMessage =
                        `Apakah Anda yakin ingin mengubah status produksi menjadi "${this.statusLabels[status] || status}"?`;
                    this.showModal = true;
                },

                async confirmUpdate() {
                    this.showModal = false;

                    const token = document.querySelector('meta[name=csrf-token]').content;

                    try {
                        const res = await fetch(`/produksi/${this.pendingId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                status: this.pendingStatus
                            })
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            this.notify(result.message || 'Gagal memperbarui status', 'error');
                            return;
                        }

                        this.notify(result.message || 'Status berhasil diperbarui', 'success');

                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);

                    } catch (err) {
                        console.error(err);
                        this.notify('Terjadi kesalahan saat memperbarui status', 'error');
                    }
                },

                notify(msg, type = 'info') {
                    this.notifMessage = msg;
                    this.notifType = type;
                    this.showNotif = true;
                    setTimeout(() => (this.showNotif = false), 3000);
                },

                init() {
                    console.log('🧩 Detail Produksi Loaded');
                }
            }
        }
    </script>

@endsection
