@extends('layouts.app')

@section('title', 'Daftar Penjualan')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Smooth transitions */
        .filter-panel {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 0;
            overflow: hidden;
            opacity: 0;
        }

        .filter-panel.show {
            max-height: 500px;
            opacity: 1;
        }

        table,
        tr,
        td {
            overflow: visible !important;
        }
    </style>

    {{-- CSRF meta --}}
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

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#FFEAE6; border-color:#FCA5A5; color:#B91C1C;">
                <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Gagal</div>
                    <div>{{ session('error') }}</div>
                </div>
                <button class="ml-auto" @click="show=false"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <div id="penjualan-page" x-data="penjualanPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Penjualan Baru
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari" x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#344579] hover:text-white transition"
                    :class="{ 'bg-[#344579] text-white': showFilter || hasActiveFilters() }">
                    <i class="fa-solid fa-sliders"></i>
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-1.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- FILTER PANEL (style mengikuti pembelian) --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                {{-- Filter No Faktur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No. Nota</label>
                    <input type="text" placeholder="Cari No. Nota" x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="filters.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Pelanggan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <input type="text" placeholder="Cari Pelanggan" x-model="filters.pelanggan"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Status Bayar --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Bayar</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Pilih Status</option>
                        <option value="lunas">Lunas</option>
                        <option value="belum">Belum Lunas</option>
                        <option value="retur">Retur</option>
                    </select>
                </div>
                {{-- Filter Status Pengiriman --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Pengiriman</label>
                    <select x-model="filters.status_pengiriman"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Pilih Status</option>
                        <option value="Perlu Diantar">Perlu Diantar</option>
                        <option value="Dalam Pengiriman">Dalam Pengiriman</option>
                        <option value="Diterima">Diterima</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> penjualan
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-white hover:bg-red-600 bg-red-400">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset
                    </button>
                </div>
            </div>
        </div>


        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-center text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_faktur')">
                                No Faktur
                                <i class="fa-solid" :class="sortIcon('no_faktur')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('tanggal')">
                                Tanggal
                                <i class="fa-solid" :class="sortIcon('tanggal')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('pelanggan')">
                                Pelanggan
                                <i class="fa-solid" :class="sortIcon('pelanggan')"></i>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total')">
                                Total
                                <i class="fa-solid" :class="sortIcon('total')"></i>
                            </th>
                            <th class="px-4 py-3">Status Bayar</th>
                            <th class="px-4 py-3">Status Pengiriman</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200 text-center">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 font-medium" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="fmtTanggal(r.tanggal)"></td>
                                <td
                                    class="px-4 py-3 text-green-600 font-medium whitespace-normal break-words leading-snug max-w-[220px]">
                                    <a :href="r.url" class="hover:underline hover:text-[#2e3e6a] transition block"
                                        x-text="r.pelanggan">
                                    </a>
                                </td>



                                <td class="px-4 py-3 text-right font-semibold" x-text="formatRupiah(r.total)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeClass(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                        <span x-text="statusLabel(r.status)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="r.status_pengiriman !== '-'">
                                        <span
                                            class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                            :class="badgeKirim(r.status_pengiriman)">
                                            <span class="w-2 h-2 rounded-full"
                                                :class="dotKirim(r.status_pengiriman)"></span>
                                            <span x-text="r.status_pengiriman"></span>
                                        </span>
                                    </template>
                                    <template x-if="r.status_pengiriman === '-'">
                                        <span class="text-slate-400">-</span>
                                    </template>
                                </td>

                                <!-- tombol aksi -->
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="8" class="px-4 py-8">
                                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-2"></i>
                                <p class="text-slate-400">Tidak ada data penjualan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button type="button" @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button type="button" @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button type="button" x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] hover:text-white cursor-pointer"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>

                    <button type="button" @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button type="button" @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>


        {{-- 🗑️ DELETE MODAL (Modern Design) --}}
        <div x-cloak x-show="showDeleteModal" aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen">

            <!-- 🌫 Overlay -->
            <div x-show="showDeleteModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all" @click="closeDelete()"></div>

            <!-- 💎 Modal Card -->
            <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                class="relative bg-white/95 backdrop-blur-sm w-[480px]
               rounded-2xl shadow-[0_10px_35px_-5px_rgba(239,68,68,0.3)]
               border border-red-100 transform transition-all overflow-hidden"
                @click.away="closeDelete()">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-red-50 to-rose-50 
            border-b border-red-100 px-5 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                        </div>
                        <h3 class="text-base font-semibold text-red-700">
                            Konfirmasi Hapus
                        </h3>
                    </div>
                    <button @click="closeDelete()" class="text-red-400 hover:text-red-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 bg-white">
                    <p class="text-slate-700 leading-relaxed">
                        Apakah Anda yakin ingin menghapus penjualan
                        <span class="font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded"
                            x-text="deleteItem.no_faktur"></span>
                        untuk pelanggan
                        <span class="font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded"
                            x-text="deleteItem.pelanggan"></span>?
                    </p>

                    <!-- Warning Box -->
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-amber-600 mt-0.5"></i>
                        <div class="text-sm text-amber-700">
                            <p class="font-medium">Perhatian:</p>
                            <p class="mt-1">Tindakan ini akan menghapus data dan mengembalikan stok. Proses ini
                                <strong>tidak dapat dibatalkan</strong>.</p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" @click="closeDelete()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 
                hover:bg-white hover:border-slate-400 transition-all font-medium">
                        <i class="fa-solid fa-xmark mr-1.5"></i> Batal
                    </button>
                    <button type="button" @click="doDelete()"
                        class="px-5 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 
                transition-all shadow-sm hover:shadow-md font-medium group">
                        <i class="fa-solid fa-trash mr-1.5 group-hover:scale-110 transition-transform"></i>
                        Hapus
                    </button>
                </div>
            </div>
        </div>

        {{-- 🧾 PRINT MODAL (Modern Design) --}}
        <div x-cloak x-show="showPrintModal" aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen">

            <!-- 🌫 Overlay -->
            <div x-show="showPrintModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all" @click="closePrint()"></div>

            <!-- 💎 Modal Card -->
            <div x-show="showPrintModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                class="relative bg-white/95 backdrop-blur-sm w-[420px]
               rounded-2xl shadow-[0_10px_35px_-5px_rgba(51,73,118,0.25)]
               border border-slate-200 transform transition-all overflow-hidden"
                @click.away="closePrint()">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] 
            border-b border-slate-200 px-5 py-3 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-base font-semibold text-[#334976] flex items-center gap-2">
                        <i class="fa-solid fa-print text-[#334976]"></i>
                        Pilih Jenis Nota
                    </h3>
                    <button @click="closePrint()" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-3 bg-white">
                    <p class="text-slate-600 mb-4">Pilih format cetak nota:</p>

                    <!-- ✅ GANTI button biasa jadi button dengan @click printNota() -->
                    <button @click="printNota('kecil')" type="button"
                        class="w-full px-4 py-2.5 rounded-lg text-white bg-blue-600 hover:bg-blue-700
                font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-receipt mr-2"></i> Print Nota Kecil
                    </button>

                    <button @click="printNota('besar')" type="button"
                        class="w-full px-4 py-2.5 rounded-lg text-white bg-green-600 hover:bg-green-700
                font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-file-invoice mr-2"></i> Print Nota Besar
                    </button>
                </div>

                <!-- Footer -->
                <div class="flex justify-end px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl">
                    <button type="button" @click="closePrint()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 
                hover:bg-slate-100 transition font-medium">
                        Batal
                    </button>
                </div>
            </div>
        </div>

        <!-- ✅ Floating dropdown portal -->
        <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown" data-dropdown
            class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
            :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">
            <button @click="window.location = dropdownData.url"
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-eye text-blue-500"></i> Detail
            </button>
            <!-- Tombol Print SELALU muncul -->
            <button
                @click="!['pending'].includes(dropdownData.status) && !dropdownData.is_draft ? openPrintModal(dropdownData) : null"
                :disabled="['pending'].includes(dropdownData.status) || dropdownData.is_draft"
                class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 rounded transition"
                :class="{
                    'text-slate-400 cursor-not-allowed bg-slate-50': ['pending'].includes(dropdownData.status) ||
                        dropdownData.is_draft,
                    'text-slate-700 hover:bg-slate-50': !(['pending'].includes(dropdownData.status) || dropdownData
                        .is_draft)
                }">
                <i class="fa-solid fa-print"
                    :class="(['pending'].includes(dropdownData.status) || dropdownData.is_draft) ? 'text-slate-400' :
                    'text-green-500'"></i>
                <span>Print</span>
            </button>

            <button @click="confirmDelete(dropdownData)"
                class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
        </div>

    </div>

    @php
        $penjualansJson = $penjualans
            ->map(function ($p) {
                $statusBayarMap = [
                    'paid' => 'lunas',
                    'unpaid' => 'belum',
                    'return' => 'retur',
                ];

                $status = $statusBayarMap[$p->status_bayar] ?? 'belum';
                if ($p->is_draft) {
                    $status = 'pending';
                }

                $statusKirimMap = [
                    'perlu_dikirim' => 'Perlu Diantar',
                    'dalam_pengiriman' => 'Dalam Pengiriman',
                    'diterima' => 'Diterima',
                ];

                $tanggal =
                    $p->tanggal instanceof \Carbon\Carbon
                        ? $p->tanggal->timezone('Asia/Makassar')->format('Y-m-d H:i:s')
                        : $p->tanggal ?? null;

                $statusPengiriman = '-';
                if ($p->mode === 'antar') {
                    $statusPengiriman = $p->pengiriman
                        ? $statusKirimMap[$p->pengiriman->status_pengiriman] ?? '-'
                        : 'Perlu Diantar';
                }

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->no_faktur,
                    'tanggal' => $tanggal,
                    'pelanggan' => optional($p->pelanggan)->nama_pelanggan ?? 'Customer',
                    'total' => (float) ($p->total ?? 0),
                    'status' => $status,
                    'status_pengiriman' => $statusPengiriman,
                    'url' => route('penjualan.show', $p->id),
                    'is_draft' => (bool) $p->is_draft,
                    'items' => $p->items
                        ->map(fn($it) => optional($it->item)->nama_item ?? ($it->item_id ?? ''))
                        ->filter()
                        ->implode(', '),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function penjualanPage() {
            return {
                showFilter: false,
                q: '',
                filters: {
                    no_faktur: '',
                    pelanggan: '',
                    tanggal: '',
                    status: '',
                    status_pengiriman: ''
                },

                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,

                openActionId: null,
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                _outsideClickHandler: null,

                showDeleteModal: false,
                showPrintModal: false,
                deleteItem: {},
                printItem: {},

                data: @json($penjualansJson),
                sortBy: 'tanggal',
                sortDir: 'desc',

                init() {
                    // Pastikan dropdown tertutup ketika keluar halaman
                    window.addEventListener('beforeunload', () => this.closeDropdown());
                },

                formatNamaPelangganWbr(nama) {
                    if (!nama) return '-';
                    const words = nama.trim().split(/\s+/);
                    if (words.length <= 3) return words.join(' ');

                    const groups = [];
                    for (let i = 0; i < words.length; i += 3) {
                        groups.push(words.slice(i, i + 3).join(' '));
                    }

                    // tambahkan <wbr> + spasi agar kata berikutnya tetap terpisah
                    return groups.join('<wbr> ');
                },





                // --- FORMATTERS ---
                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n || 0);
                },

                fmtTanggal(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);
                    if (isNaN(d)) return iso;
                    const dd = String(d.getDate()).padStart(2, '0');
                    const mm = String(d.getMonth() + 1).padStart(2, '0');
                    const yyyy = d.getFullYear();
                    const hh = String(d.getHours()).padStart(2, '0');
                    const mi = String(d.getMinutes()).padStart(2, '0');
                    return `${dd}-${mm}-${yyyy}, ${hh}:${mi}`;
                },

                sortIcon(field) {
                    if (this.sortBy !== field) return 'fa-sort ml-2 text-slate-400';
                    return this.sortDir === 'asc' ?
                        'fa-arrow-up ml-2 text-[#344579]' :
                        'fa-arrow-down ml-2 text-[#344579]';
                },

                // --- FILTER + SORT ---
                filteredList() {
                    const q = this.q.trim().toLowerCase();

                    let list = this.data.filter(r => {
                        if (q && !(`${r.no_faktur} ${r.pelanggan} ${r.items}`.toLowerCase().includes(q)))
                            return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur
                                .toLowerCase()))
                            return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan
                                .toLowerCase()))
                            return false;
                        if (this.filters.tanggal && r.tanggal && r.tanggal.split(' ')[0] !== this.filters.tanggal)
                            return false;
                        if (this.filters.status && r.status !== this.filters.status)
                            return false;
                        if (this.filters.status_pengiriman && r.status_pengiriman !== this.filters
                            .status_pengiriman)
                            return false;
                        return true;
                    });

                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => {
                        const va = a[this.sortBy] ?? '';
                        const vb = b[this.sortBy] ?? '';

                        if (!isNaN(Date.parse(va)) && !isNaN(Date.parse(vb)))
                            return (new Date(va) - new Date(vb)) * dir;

                        if (!isNaN(parseFloat(va)) && !isNaN(parseFloat(vb)))
                            return (parseFloat(va) - parseFloat(vb)) * dir;

                        return va.toString().localeCompare(vb.toString()) * dir;
                    });

                    return list;
                },

                filteredTotal() {
                    return this.filteredList().length;
                },

                totalPages() {
                    return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize));
                },

                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },

                goToPage(n) {
                    const t = this.totalPages();
                    if (n < 1) n = 1;
                    if (n > t) n = t;
                    this.currentPage = n;
                    this.closeDropdown();
                },

                prev() {
                    if (this.currentPage > 1) this.currentPage--;
                    this.closeDropdown();
                },

                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++;
                    this.closeDropdown();
                },

                pagesToShow() {
                    const total = this.totalPages();
                    const max = this.maxPageButtons;
                    const current = this.currentPage;

                    if (total <= max) return Array.from({
                        length: total
                    }, (_, i) => i + 1);

                    const pages = [];
                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, current - side);
                    const right = Math.min(total - 1, current + side);

                    pages.push(1);
                    if (left > 2) pages.push('...');
                    for (let i = left; i <= right; i++) pages.push(i);
                    if (right < total - 1) pages.push('...');
                    pages.push(total);

                    return pages;
                },

                // --- FILTER HELPERS ---
                hasActiveFilters() {
                    return (
                        this.filters.no_faktur ||
                        this.filters.pelanggan ||
                        this.filters.tanggal ||
                        this.filters.status ||
                        this.filters.status_pengiriman
                    );
                },

                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.no_faktur) count++;
                    if (this.filters.pelanggan) count++;
                    if (this.filters.tanggal) count++;
                    if (this.filters.status) count++;
                    if (this.filters.status_pengiriman) count++;
                    return count;
                },

                resetFilters() {
                    this.filters = {
                        no_faktur: '',
                        pelanggan: '',
                        tanggal: '',
                        status: '',
                        status_pengiriman: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                // --- DROPDOWN FLOATING FIX ---
                openDropdown(row, event) {
                    // jangan stopPropagation di sini supaya event global tetap konsisten
                    // event.stopPropagation();

                    // toggle behavior: jika sama, close
                    if (this.openActionId === row.id) {
                        this.closeDropdown();
                        return;
                    }

                    this.openActionId = row.id;
                    this.dropdownData = row;

                    const rect = event.currentTarget.getBoundingClientRect();
                    const dropdownHeight = 120;

                    // hitung ruang relatif ke viewport untuk menentukan di atas/di bawah
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    // konversi koordinat viewport -> dokumen
                    const docTopBelow = rect.bottom + window.scrollY + 6; // posisi jika ditaruh di bawah
                    const docTopAbove = rect.top + window.scrollY - dropdownHeight - 6; // posisi jika ditaruh di atas
                    const docLeft = rect.right + window.scrollX - 176;

                    if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                        // tempatkan di atas (menggunakan koordinat dokumen)
                        this.dropdownPos = {
                            top: docTopAbove + 'px',
                            left: docLeft + 'px'
                        };
                    } else {
                        // tempatkan di bawah (menggunakan koordinat dokumen)
                        this.dropdownPos = {
                            top: docTopBelow + 'px',
                            left: docLeft + 'px'
                        };
                    }


                    this.dropdownVisible = true;

                    // tambahkan listener setelah satu tick agar klik pembuka tidak langsung menutupnya
                    this._outsideClickHandler = this.handleOutsideClick.bind(this);
                    setTimeout(() => {
                        document.addEventListener('click', this._outsideClickHandler);
                    }, 0);

                    // juga tutup saat resize atau scroll (opsional)
                    this._resizeHandler = this.closeDropdown.bind(this);
                    window.addEventListener('resize', this._resizeHandler);
                },

                handleOutsideClick(e) {
                    const dropdownEl = document.querySelector('[data-dropdown]');
                    const isInsideDropdown = dropdownEl && dropdownEl.contains(e.target);
                    const isTriggerButton = !!e.target.closest('[data-dropdown-open-button]');

                    // jika klik bukan di dropdown dan bukan di tombol pemicu → tutup
                    if (!isInsideDropdown && !isTriggerButton) {
                        this.closeDropdown();
                    }
                },

                closeDropdown() {
                    this.dropdownVisible = false;
                    this.openActionId = null;
                    this.dropdownData = {};

                    if (this._outsideClickHandler) {
                        document.removeEventListener('click', this._outsideClickHandler);
                        this._outsideClickHandler = null;
                    }

                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                        this._resizeHandler = null;
                    }
                },



                // --- SORT ---
                toggleSort(field) {
                    if (this.sortBy === field) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortDir = 'asc';
                    }
                    this.currentPage = 1;
                },

                // --- STATUS STYLING ---
                badgeClass(st) {
                    if (st === 'lunas') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'belum') return 'bg-slate-100 text-slate-600 border border-slate-200';
                    if (st === 'retur') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    if (st === 'pending') return 'bg-amber-50 text-amber-700 border border-amber-200';
                    return 'bg-slate-50 text-slate-700 border border-slate-200';
                },

                dotClass(st) {
                    if (st === 'lunas') return 'bg-green-500';
                    if (st === 'belum') return 'bg-slate-400';
                    if (st === 'retur') return 'bg-rose-500';
                    if (st === 'pending') return 'bg-amber-500';
                    return 'bg-slate-500';
                },

                statusLabel(st) {
                    if (st === 'lunas') return 'Lunas';
                    if (st === 'belum') return 'Belum Lunas';
                    if (st === 'retur') return 'Retur';
                    if (st === 'pending') return 'Pending';
                    return '-';
                },

                badgeKirim(st) {
                    if (st === 'Perlu Diantar') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'Diterima') return 'bg-green-50 text-green-700 border border-green-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },

                dotKirim(st) {
                    if (st === 'Perlu Diantar') return 'bg-orange-500';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-500';
                    if (st === 'Diterima') return 'bg-green-500';
                    return 'bg-slate-500';
                },

                // --- DELETE & PRINT ---
                confirmDelete(item) {
                    if (!item) return;
                    this.closeDropdown();
                    this.deleteItem = {
                        ...item
                    };
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                },

                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const url = `/penjualan/${this.deleteItem.id}/delete`;

                    try {
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            }
                        });

                        const result = await res.json();

                        if (res.ok && result.success) {
                            // ✅ Hapus dari data array
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);

                            this.showNotification('success', result.message || 'Data berhasil dihapus');

                            // ✅ Adjust halaman jika kosong
                            if (this.currentPage > this.totalPages()) {
                                this.currentPage = this.totalPages();
                            }
                        } else {
                            // ❌ Error dari backend
                            this.showNotification('error', result.message || 'Gagal menghapus data');
                        }

                    } catch (e) {
                        console.error('Delete error:', e);
                        this.showNotification('error', 'Terjadi kesalahan koneksi');
                    } finally {
                        this.closeDelete();
                    }
                },

                openPrintModal(item) {
                    if (!item || item.status === 'pending' || item.is_draft) return;
                    this.closeDropdown();
                    this.printItem = {
                        ...item
                    };
                    this.showPrintModal = true;
                },

                closePrint() {
                    this.showPrintModal = false;
                    this.printItem = {};
                },

                // ✅ GANTI function doPrint() dengan printNota() yang sama seperti create/show
                async printNota(type) {
                    if (!this.printItem.id) return;

                    try {
                        const url = `/penjualan/${this.printItem.id}/print?type=${type}`;
                        const res = await fetch(url);

                        if (!res.ok) throw new Error("Gagal memuat nota");

                        const html = await res.text();
                        const printWindow = window.open('', '_blank', 'width=800,height=600');

                        if (!printWindow) {
                            this.showNotification('error', "Popup diblokir, izinkan popup untuk melanjutkan.");
                            return;
                        }

                        printWindow.document.write(html);
                        printWindow.document.close();

                        // ✅ Tunggu dokumen siap
                        printWindow.onload = () => {
                            setTimeout(() => {
                                printWindow.focus();
                                printWindow.print();

                                // ✅ Auto close setelah print dialog
                                printWindow.onafterprint = () => {
                                    printWindow.close();
                                };

                                // ✅ Auto-close cepat (2 detik) - baik user print atau cancel
                                setTimeout(() => {
                                    if (!printWindow.closed) {
                                        printWindow.close();
                                    }
                                }, 2000);

                            }, 500);
                        };

                        // ✅ Tutup modal setelah popup dibuka
                        this.closePrint();

                    } catch (err) {
                        console.error(err);
                        this.showNotification('error', "Gagal mencetak nota, coba lagi.");
                    }
                },

                showNotification(type, message) {
                    const bg = type === 'error' ?
                        'bg-rose-50 text-rose-700 border border-rose-200' :
                        'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    const icon = type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check';

                    const el = document.createElement('div');
                    el.className =
                        `fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-md border shadow ${bg}`;
                    el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;

                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3500);
                },
            };
        }
    </script>


@endsection
