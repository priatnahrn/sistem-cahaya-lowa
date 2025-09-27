@extends('layouts.app')

@section('title', 'Daftar Item')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Toasts --}}
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
                <a href="{{ route('items.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari item..." x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                              focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#2e3e6a] hover:text-white"
                    :class="{ 'bg-[#344579] text-white': hasActiveFilters() }">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- ENHANCED FILTER --}}
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

                {{-- Filter Kategori (masih pakai dropdown suggestion) --}}
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
                    <label class="block text-sm font-medium text-slate-700 mb-2">Stok</label>
                    <select x-model="filters.stok"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]
                       appearance-none pr-10">
                        <option value="">Semua</option>
                        <option value="tersedia">Stok Tersedia (&gt; 0)</option>
                        <option value="kosong">Stok Kosong (= 0)</option>
                        <option value="rendah">Stok Rendah (&lt; 10)</option>
                    </select>
                    <i
                        class="fa-solid fa-chevron-down absolute right-3 top-12 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>

            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> item
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i>
                        Reset Filter
                    </button>
                    <button type="button" @click="showFilter = false"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a]">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>


        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3">Foto</th>

                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('kode')">
                                Kode Item
                                <i class="fa-solid"
                                    :class="sortBy === 'kode' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('nama')">
                                Nama Item
                                <i class="fa-solid"
                                    :class="sortBy === 'nama' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('kategori')">
                                Kategori
                                <i class="fa-solid"
                                    :class="sortBy === 'kategori' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('stock')">
                                Stok
                                <i class="fa-solid"
                                    :class="sortBy === 'stock' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>

                            <th class="px-4 py-3">Satuan</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>

                                {{-- FOTO --}}
                                <td class="px-4 py-3">
                                    <template x-if="r.foto_path">
                                        <img :src="'/storage/' + r.foto_path" alt="Foto Item"
                                            class="h-12 w-12 object-cover rounded border border-slate-200">
                                    </template>
                                    <template x-if="!r.foto_path">
                                        <div
                                            class="h-12 w-12 bg-slate-100 flex items-center justify-center text-slate-400 text-xs">
                                            N/A
                                        </div>
                                    </template>
                                </td>

                                {{-- KODE --}}
                                <td class="px-4 py-3"><span class="font-mono text-sm" x-text="r.kode"></span></td>

                                {{-- NAMA --}}
                                <td class="px-4 py-3">
                                    <a :href="r.url" class="text-green-600 font-normal hover:underline"
                                        x-text="r.nama" @click="openActionId=null"></a>
                                </td>

                                <td class="px-4 py-3" x-text="r.kategori"></td>
                                <td class="px-4 py-3">
                                    <span x-text="r.stock ?? '-'"></span>
                                    <span x-show="r.stock === 0"
                                        class="ml-2 px-2 py-1 bg-red-100 text-red-700 text-xs rounded">
                                        Kosong
                                    </span>
                                    <span x-show="r.stock > 0 && r.stock < 10"
                                        class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-700 text-xs rounded">
                                        Rendah
                                    </span>
                                </td>

                                {{-- SATUAN --}}
                                <td class="px-4 py-3">
                                    <template x-if="Array.isArray(r.satuans) && r.satuans.length">
                                        <div>
                                            <template x-for="(s, i) in r.satuans" :key="i">
                                                <div class="text-sm" x-text="s.nama_satuan"></div>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!Array.isArray(r.satuans) || !r.satuans.length">
                                        <span class="text-slate-400 text-xs">-</span>
                                    </template>
                                </td>

                                {{-- ACTIONS --}}
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" @click="toggleActions(r.id)"
                                        class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div x-cloak x-show="openActionId===r.id" @click.away="openActionId=null" x-transition
                                        class="absolute right-2 mt-2 w-40 bg-white shadow rounded-md border border-slate-200 z-20">
                                        <ul class="py-1">
                                            <li>
                                                <a :href="r.url"
                                                    class="block px-4 py-2 hover:bg-slate-50 text-left"
                                                    @click="openActionId=null">
                                                    <i class="fa-solid fa-eye mr-2"></i> Detail
                                                </a>
                                            </li>
                                            <li>
                                                <button type="button" @click="confirmDelete(r)"
                                                    class="w-full text-left px-4 py-2 text-red-500 hover:bg-slate-50">
                                                    <i class="fa-solid fa-trash mr-2"></i> Hapus
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="9" class="px-4 py-6">Tidak ada data item yang sesuai dengan filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] cursor-pointer"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>

                    <button @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>

        {{-- DELETE CONFIRM MODAL --}}
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeDelete()"></div>
            <div x-transition class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/3 z-10 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-red-600">Konfirmasi Hapus</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-slate-600">
                        Apakah Anda yakin ingin menghapus item
                        <span class="font-semibold" x-text="deleteItem.nama"></span> (kode:
                        <span x-text="deleteItem.kode"></span>)?
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <button @click="closeDelete()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Batal</button>
                    <button @click="doDelete()"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Hapus</button>
                </div>
            </div>
        </div>
    </div>


    @php
        $itemsJson = $items
            ->map(
                fn($it) => [
                    'id' => $it->id,
                    'kode' => $it->kode_item,
                    'nama' => $it->nama_item,
                    'kategori' => optional($it->kategori)->nama_kategori,
                    'stock' => $it->stok ?? 0,
                    'foto_path' => $it->foto_path,
                    'url' => route('items.show', $it->id),
                    'satuans' => $it->satuans
                        ->map(
                            fn($s) => [
                                'id' => $s->id,
                                'nama_satuan' => $s->nama_satuan,
                            ],
                        )
                        ->values()
                        ->toArray(),
                ],
            )
            ->toArray();
    @endphp


    <script>
        function itemsPage() {
            return {
                data: @json($itemsJson),
                q: '',
                filters: {
                    nama: '',
                    kategori: '',
                    stok: ''
                },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                showFilter: false,
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},

                // sorting state
                sortBy: 'kode', // default kolom
                sortDir: 'asc',

                init() {},

                
                // Get unique categories for dropdown
                getUniqueCategories() {
                    return [...new Set(this.data.map(item => item.kategori).filter(Boolean))].sort();
                },
               

                // Check if there are active filters
                hasActiveFilters() {
                    return this.filters.nama !== '' || this.filters.kategori !== '' || this.filters.stok !== '';
                },

                // Count active filters
                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.nama) count++;
                    if (this.filters.kategori) count++;
                    if (this.filters.stok) count++;
                    return count;
                },

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        // Search global
                        if (q && !(`${r.kode} ${r.nama}`.toLowerCase().includes(q))) return false;

                        // Filter kode
                        if (this.filters.kode && !r.kode.toLowerCase().includes(this.filters.kode.toLowerCase()))
                            return false;

                        // Filter nama
                        if (this.filters.nama && !r.nama.toLowerCase().includes(this.filters.nama.toLowerCase()))
                            return false;

                        // Filter kategori
                        if (this.filters.kategori && !r.kategori.toLowerCase().includes(this.filters.kategori
                                .toLowerCase())) return false;

                        // Filter stok
                        if (this.filters.stok) {
                            const stock = r.stock || 0;
                            switch (this.filters.stok) {
                                case 'tersedia':
                                    if (stock <= 0) return false;
                                    break;
                                case 'kosong':
                                    if (stock !== 0) return false;
                                    break;
                                case 'rendah':
                                    if (stock >= 10) return false;
                                    break;
                            }
                        }

                        return true;
                    });

                    // Sorting
                    const sortKey = this.sortBy;
                    const dir = this.sortDir === 'asc' ? 1 : -1;

                    list.sort((a, b) => {
                        const va = (a[sortKey] ?? '').toString().toLowerCase();
                        const vb = (b[sortKey] ?? '').toString().toLowerCase();

                        const an = parseFloat(va);
                        const bn = parseFloat(vb);
                        if (!isNaN(an) && !isNaN(bn)) {
                            return (an - bn) * dir;
                        }

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
                goToPage(n) {
                    this.currentPage = Math.min(Math.max(1, n), this.totalPages());
                    this.openActionId = null;
                },
                prev() {
                    if (this.currentPage > 1) this.currentPage--;
                },
                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++;
                },
                pagesToShow() {
                    const total = this.totalPages(),
                        max = this.maxPageButtons,
                        cur = this.currentPage;
                    if (total <= max) return Array.from({
                        length: total
                    }, (_, i) => i + 1);
                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, cur - side),
                        right = Math.min(total - 1, cur + side);
                    const pages = [1];
                    if (left > 2) pages.push('...');
                    for (let i = left; i <= right; i++) pages.push(i);
                    if (right < total - 1) pages.push('...');
                    pages.push(total);
                    return pages;
                },

                // sorting toggle
                toggleSort(field) {
                    if (this.sortBy === field) {
                        this.sortDir = (this.sortDir === 'asc') ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortDir = 'asc';
                    }
                    this.currentPage = 1;
                },

                toggleActions(id) {
                    this.openActionId = (this.openActionId === id) ? null : id;
                },
                confirmDelete(item) {
                    this.openActionId = null;
                    this.deleteItem = {
                        ...item
                    };
                    this.showDeleteModal = true;
                },
                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                },
                doDelete() {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;

                    fetch(`/items/${this.deleteItem.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal hapus item');
                            return res.json().catch(() => ({}));
                        })
                        .then(() => {
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);

                            if (this.currentPage > this.totalPages()) {
                                this.currentPage = this.totalPages();
                            }

                            this.closeDelete();

                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    type: 'success',
                                    message: 'Item berhasil dihapus.'
                                }
                            }));
                        })
                        .catch(err => {
                            console.error(err);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    type: 'error',
                                    message: 'Terjadi kesalahan saat menghapus item.'
                                }
                            }));
                        });
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
                }
            }
        }
    </script>
@endsection
