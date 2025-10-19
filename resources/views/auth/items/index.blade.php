@extends('layouts.app')

@section('title', 'Daftar Item')

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
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
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
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
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

    <div x-data="itemsPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">

                @can('items.create')
                    <a href="{{ route('items.create') }}"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow transition">
                        <i class="fa-solid fa-plus"></i> Tambah Item Baru
                    </a>
                @endcan

            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari item..." x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                              focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#344579] hover:text-white transition"
                    :class="{ 'bg-[#344579] text-white': showFilter || hasActiveFilters() }">
                    <i class="fa-solid fa-sliders"></i>
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- FILTER PANEL --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Filter Kode Item --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kode Item</label>
                    <input type="text" placeholder="Cari kode item..." x-model="filters.kode"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Nama Item --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Item</label>
                    <input type="text" placeholder="Cari nama item..." x-model="filters.nama"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Kategori (dropdown suggestion) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Kategori</label>
                    <div class="relative" x-data="{ open: false }">
                        <input type="text" placeholder="Cari kategori..." x-model="filters.kategori" @focus="open = true"
                            @input="open = true" @click.away="open = false"
                            class="w-full px-3 py-2 pr-8 rounded-lg border border-slate-200 text-sm text-slate-700 
                           focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <i
                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>

                        <div x-show="open && filters.kategori" x-cloak x-transition
                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2">
                                <template
                                    x-for="kategori in getUniqueCategories().filter(k => k.toLowerCase().includes(filters.kategori.toLowerCase()))"
                                    :key="kategori">
                                    <div @click="filters.kategori = kategori; open = false"
                                        class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded"
                                        x-text="kategori"></div>
                                </template>
                                <div x-show="getUniqueCategories().filter(k => k.toLowerCase().includes(filters.kategori.toLowerCase())).length === 0"
                                    class="px-3 py-2 text-sm text-slate-400 text-center">
                                    Tidak ada kategori ditemukan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter Stok --}}
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Stok</label>
                    <select x-model="filters.stok"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                           focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]
                           appearance-none pr-10">
                        <option value="">Semua</option>
                        <option value="aman">Aman (&gt; Stok Minimal)</option>
                        <option value="perlu">Perlu Pembelian (&le; Stok Minimal)</option>
                        <option value="kosong">Kosong (= 0)</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-3 bottom-3 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> item
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-white hover:bg-red-600 bg-red-400 transition">
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
                            <th class="px-4 py-3">Foto</th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('kode')">
                                Kode Item
                                <i class="fa-solid" :class="sortIcon('kode')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('nama')">
                                Nama Item
                                <i class="fa-solid" :class="sortIcon('nama')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('kategori')">
                                Kategori
                                <i class="fa-solid" :class="sortIcon('kategori')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('stock')">
                                Stok
                                <i class="fa-solid" :class="sortIcon('stock')"></i>
                            </th>
                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200 text-center">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>

                                {{-- FOTO --}}
                                <td class="px-4 py-3">
                                    <div class="flex justify-center">
                                        <template x-if="r.foto_path">
                                            <img :src="'/storage/' + r.foto_path" alt="Foto Item"
                                                class="h-12 w-12 object-cover rounded border border-slate-200">
                                        </template>
                                        <template x-if="!r.foto_path">
                                            <div
                                                class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-400 text-xs rounded border border-slate-200">
                                                N/A
                                            </div>
                                        </template>
                                    </div>
                                </td>

                                {{-- KODE --}}
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-medium" x-text="r.kode"></span>
                                </td>

                                {{-- NAMA --}}
                                <td class="px-4 py-3 text-green-600 font-medium">
                                    <a :href="r.url" class="hover:underline hover:text-[#2e3e6a] transition"
                                        x-text="r.nama">
                                    </a>
                                </td>

                                {{-- KATEGORI --}}
                                <td class="px-4 py-3 text-slate-600" x-text="r.kategori"></td>

                                {{-- STOK --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-semibold" x-text="formatStok(r.stock)"></span>

                                        <!-- Status stok badges -->
                                        <span x-show="r.stock === 0"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-700 text-xs rounded-full border border-red-200">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                            Kosong
                                        </span>
                                        <span x-show="r.stock > 0 && r.stock <= r.stok_minimal"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-amber-50 text-amber-700 text-xs rounded-full border border-amber-200">
                                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full"></span>
                                            Perlu Restock
                                        </span>
                                        <span x-show="r.stock > r.stok_minimal"
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-green-50 text-green-700 text-xs rounded-full border border-green-200">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            Aman
                                        </span>
                                    </div>
                                </td>

                                {{-- SATUAN --}}
                                <td class="px-4 py-3">
                                    <template x-if="Array.isArray(r.satuans) && r.satuans.length">
                                        <div class="flex flex-col gap-1">
                                            <template x-for="(s, i) in r.satuans" :key="i">
                                                <span class="text-xs text-slate-600" x-text="s.nama_satuan"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!Array.isArray(r.satuans) || !r.satuans.length">
                                        <span class="text-slate-400 text-xs">-</span>
                                    </template>
                                </td>

                                {{-- Action Dropdown --}}
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="{{ auth()->user()->can('items.update') || auth()->user()->can('items.delete') ? '8' : '7' }}"
                                class="px-4 py-8">
                                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-2"></i>
                                <p class="text-slate-400">Tidak ada data item.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button type="button" @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50 transition">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button type="button" @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50 transition">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button type="button" x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] hover:text-white cursor-pointer transition"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>

                    <button type="button" @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50 transition">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button type="button" @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50 transition">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>

        {{-- 🗑️ DELETE MODAL (Modern Design) --}}
        <div x-cloak x-show="showDeleteModal" aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen">

            <!-- Overlay -->
            <div x-show="showDeleteModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all" @click="closeDelete()"></div>

            <!-- Modal Card -->
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
                    class="bg-gradient-to-r from-red-50 to-rose-50 border-b border-red-100 px-5 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                        </div>
                        <h3 class="text-base font-semibold text-red-700">Konfirmasi Hapus</h3>
                    </div>
                    <button @click="closeDelete()" class="text-red-400 hover:text-red-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 bg-white">
                    <p class="text-slate-700 leading-relaxed">
                        Apakah Anda yakin ingin menghapus item
                        <span class="font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded"
                            x-text="deleteItem.nama"></span>
                        dengan kode
                        <span class="font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded font-mono"
                            x-text="deleteItem.kode"></span>?
                    </p>

                    <!-- Warning Box -->
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-amber-600 mt-0.5"></i>
                        <div class="text-sm text-amber-700">
                            <p class="font-medium">Perhatian:</p>
                            <p class="mt-1">Tindakan ini akan menghapus data item dan semua stok terkait secara permanen.
                                Proses ini <strong>tidak dapat dibatalkan</strong>.</p>
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

        {{-- Floating dropdown portal --}}
        <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown" data-dropdown
            class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
            :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">
            <button @click="window.location = dropdownData.url"
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700 rounded-t-lg transition">
                <i class="fa-solid fa-eye text-blue-500"></i> Detail
            </button>
            @can('items.delete')
                <button @click="confirmDelete(dropdownData)"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600 rounded-b-lg transition">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            @endcan
        </div>

    </div>

    @php
        $itemsJson = $items
            ->map(function ($it) {
                return [
                    'id' => $it->id,
                    'kode' => $it->kode_item,
                    'nama' => $it->nama_item,
                    'kategori' => optional($it->kategori)->nama_kategori,
                    'stock' => (float) ($it->gudangItems->sum('total_stok') ?? 0),
                    'stok_minimal' => (int) ($it->stok_minimal ?? 0),
                    'foto_path' => $it->foto_path,
                    'url' => route('items.show', $it->id),
                    'satuans' => $it->gudangItems
                        ->groupBy('satuan_id')
                        ->map(function ($group) {
                            return [
                                'id' => $group->first()->satuan_id,
                                'nama_satuan' => $group->first()->satuan->nama_satuan,
                                'stok' => $group->sum('stok'),
                            ];
                        })
                        ->values()
                        ->toArray(),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function itemsPage() {
            return {
                data: @json($itemsJson),
                q: '',
                filters: {
                    kode: '',
                    nama: '',
                    kategori: '',
                    stok: ''
                },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                showFilter: false,
                openActionId: null,
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                _outsideClickHandler: null,
                _resizeHandler: null,
                showDeleteModal: false,
                deleteItem: {},

                // sorting
                sortBy: 'kode',
                sortDir: 'asc',

                init() {
                    window.addEventListener('beforeunload', () => this.closeDropdown());
                },

                // --- HELPERS ---
                getUniqueCategories() {
                    return [...new Set(this.data.map(item => item.kategori).filter(Boolean))].sort();
                },

                formatStok(val) {
                    if (val == null || val === '') return '0';
                    const num = parseFloat(val);
                    if (Number.isInteger(num)) {
                        return num.toString();
                    }
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).replace('.', ',');
                },

                // --- FILTER HELPERS ---
                hasActiveFilters() {
                    return this.filters.kode || this.filters.nama || this.filters.kategori || this.filters.stok;
                },

                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.kode) count++;
                    if (this.filters.nama) count++;
                    if (this.filters.kategori) count++;
                    if (this.filters.stok) count++;
                    return count;
                },

                resetFilters() {
                    this.filters = {
                        kode: '',
                        nama: '',
                        kategori: '',
                        stok: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                // --- FILTER + SORT ---
                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        if (q && !(`${r.kode} ${r.nama}`.toLowerCase().includes(q))) return false;
                        if (this.filters.kode && !r.kode.toLowerCase().includes(this.filters.kode.toLowerCase()))
                            return false;
                        if (this.filters.nama && !r.nama.toLowerCase().includes(this.filters.nama.toLowerCase()))
                            return false;
                        if (this.filters.kategori && !r.kategori.toLowerCase().includes(this.filters.kategori
                                .toLowerCase()))
                            return false;

                        // Filter stok
                        if (this.filters.stok) {
                            const stock = r.stock || 0;
                            const min = r.stok_minimal || 0;
                            switch (this.filters.stok) {
                                case 'aman':
                                    if (stock <= min) return false;
                                    break;
                                case 'perlu':
                                    if (!(stock > 0 && stock <= min)) return false;
                                    break;
                                case 'kosong':
                                    if (stock !== 0) return false;
                                    break;
                            }
                        }

                        return true;
                    });

                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => {
                        const va = (a[this.sortBy] ?? '').toString().toLowerCase();
                        const vb = (b[this.sortBy] ?? '').toString().toLowerCase();
                        const an = parseFloat(va),
                            bn = parseFloat(vb);
                        if (!isNaN(an) && !isNaN(bn)) return (an - bn) * dir;
                        return va.localeCompare(vb) * dir;
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

                // --- PAGINATION ---
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
                    const cur = this.currentPage;

                    if (total <= max) return Array.from({
                        length: total
                    }, (_, i) => i + 1);

                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, cur - side);
                    const right = Math.min(total - 1, cur + side);

                    const pages = [1];
                    if (left > 2) pages.push('...');
                    for (let i = left; i <= right; i++) pages.push(i);
                    if (right < total - 1) pages.push('...');
                    pages.push(total);

                    return pages;
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

                sortIcon(field) {
                    if (this.sortBy !== field) return 'fa-sort ml-2 text-slate-400';
                    return this.sortDir === 'asc' ?
                        'fa-arrow-up ml-2 text-[#344579]' :
                        'fa-arrow-down ml-2 text-[#344579]';
                },

                // --- DROPDOWN FLOATING ---
                openDropdown(row, event) {
                    if (this.openActionId === row.id) {
                        this.closeDropdown();
                        return;
                    }

                    this.openActionId = row.id;
                    this.dropdownData = row;

                    const rect = event.currentTarget.getBoundingClientRect();
                    const dropdownHeight = 90;

                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    const docTopBelow = rect.bottom + window.scrollY + 6;
                    const docTopAbove = rect.top + window.scrollY - dropdownHeight - 6;
                    const docLeft = rect.right + window.scrollX - 176;

                    if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                        this.dropdownPos = {
                            top: docTopAbove + 'px',
                            left: docLeft + 'px'
                        };
                    } else {
                        this.dropdownPos = {
                            top: docTopBelow + 'px',
                            left: docLeft + 'px'
                        };
                    }

                    this.dropdownVisible = true;

                    this._outsideClickHandler = this.handleOutsideClick.bind(this);
                    setTimeout(() => {
                        document.addEventListener('click', this._outsideClickHandler);
                    }, 0);

                    this._resizeHandler = this.closeDropdown.bind(this);
                    window.addEventListener('resize', this._resizeHandler);
                },

                handleOutsideClick(e) {
                    const dropdownEl = document.querySelector('[data-dropdown]');
                    const isInsideDropdown = dropdownEl && dropdownEl.contains(e.target);
                    const isTriggerButton = !!e.target.closest('[data-dropdown-open-button]');

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

                // --- DELETE ---
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
                    const url = `/items/${this.deleteItem.id}`;

                    try {
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            }
                        });

                        const result = await res.json().catch(() => ({}));

                        if (res.ok) {
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);

                            this.showNotification('success', 'Item berhasil dihapus');

                            if (this.currentPage > this.totalPages()) {
                                this.currentPage = this.totalPages();
                            }
                        } else {
                            this.showNotification('error', result.message || 'Gagal menghapus data');
                        }

                    } catch (e) {
                        console.error('Delete error:', e);
                        this.showNotification('error', 'Terjadi kesalahan koneksi');
                    } finally {
                        this.closeDelete();
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
                }
            }
        }
    </script>
@endsection
