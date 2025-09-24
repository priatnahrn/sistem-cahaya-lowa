@extends('layouts.app')

@section('title', 'Kategori Item')

@section('content')
    <style>
        [x-cloak] { display: none !important; }
    </style>

    {{-- Toasts container (Alpine-managed) --}}
    <div x-data="toasts()" class="fixed top-6 right-6 space-y-3 z-50 w-80" aria-live="polite" aria-atomic="true">
        <template x-for="(t, i) in items" :key="t.id">
            <div x-show="t.show" x-transition class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
                 :class="t.type === 'success' ? 'bg-[#ECFDF5] border-[#A7F3D0] text-[#065F46]' : 'bg-[#FFEAE6] border-[#FCA5A5] text-[#B91C1C]'">
                <i class="fa-solid" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                <div>
                    <div class="font-semibold" x-text="t.type === 'success' ? 'Berhasil' : 'Gagal'"></div>
                    <div x-text="t.message"></div>
                </div>
                <button class="ml-auto" @click="close(i)" aria-label="Tutup">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>

        {{-- show server-side session flash jika ada (non-AJAX page load) --}}
        @if(session('success'))
            <div x-init="$root.push('success', @js(session('success')))" style="display:none"></div>
        @endif
        @if(session('error'))
            <div x-init="$root.push('error', @js(session('error')))" style="display:none"></div>
        @endif
    </div>

    <div x-data="kategoriPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('items.categories.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Kategori Baru
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Search" x-model="q"
                           class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <button type="button" @click="showFilter=!showFilter"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </button>
            </div>
        </div>

        {{-- FILTER --}}
        <div x-show="showFilter" x-collapse x-transition
             class="bg-white border border-slate-200 rounded-xl px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm text-slate-500 mb-1">Nama Kategori</label>
                <input type="text" placeholder="Cari Nama Kategori" x-model="filters.nama"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div class="flex items-end md:col-span-2">
                <button type="button" @click="resetFilters()"
                        class="ml-auto px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Reset
                </button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3 w-[60px]">No.</th>

                        <th class="px-4 py-3 cursor-pointer" @click="toggleSort('nama')">
                            Nama Kategori
                            <i class="fa-solid" :class="sortBy === 'nama' ? (sortDir === 'asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                        </th>

                        <th class="px-2 py-3"></th>
                    </tr>
                    </thead>

                    <tbody>
                    <template x-for="(r, idx) in pagedData()" :key="r.id">
                        <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                            <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                            <td class="px-4 py-3">
                                <a :href="r.url" class="text-[#344579] font-semibold hover:underline" x-text="r.nama" @click="openActionId = null"></a>
                            </td>
                            <td class="px-2 py-3 text-right relative">
                                <button type="button" @click="toggleActions(r.id)" class="px-2 py-1 rounded hover:bg-slate-100">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div x-cloak x-show="openActionId === r.id" @click.away="openActionId = null" x-transition
                                     class="absolute right-2 mt-2 w-40 bg-white shadow rounded-md border border-slate-200 z-20">
                                    <ul class="py-1">
                                        <li>
                                            <a :href="r.url" class="block px-4 py-2 hover:bg-slate-50 text-left" @click="openActionId = null">
                                                <i class="fa-solid fa-eye mr-2"></i> Detail
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" @click="confirmDelete(r)" class="w-full text-left px-4 py-2 text-red-500 hover:bg-slate-50">
                                                <i class="fa-solid fa-trash mr-2"></i> Hapus
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredTotal() === 0" class="text-center text-slate-500">
                        <td colspan="3" class="px-4 py-6">Tidak ada data kategori.</td>
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
                            <button type="button" x-show="p !== '...'" @click="goToPage(p)" x-text="p"
                                    :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                    class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-slate-50"></button>
                            <span x-show="p === '...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
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

        {{-- DELETE CONFIRM MODAL --}}
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeDelete()"></div>
            <div x-transition class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/3 z-10 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-red-600">Konfirmasi Hapus</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-slate-600">
                        Apakah Anda yakin ingin menghapus kategori
                        <span class="font-semibold" x-text="deleteItem.nama"></span>?
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <button type="button" @click="closeDelete()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="button" @click="doDelete()" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $categoryList = $categories->map(function ($k) {
            return [
                'id' => $k->id,
                'nama' => $k->nama_kategori,
                'url' => route('items.categories.show', $k->id),
                'deleteUrl' => route('items.categories.destroy', $k->id),
            ];
        })->toArray();
    @endphp

    <script>
        // Toast manager (used globally at top)
        function toasts() {
            return {
                items: [],
                _id: 1,
                push(type, message, timeout = 4000) {
                    const id = this._id++;
                    this.items.push({ id, type, message, show: true });
                    setTimeout(() => {
                        const idx = this.items.findIndex(t => t.id === id);
                        if (idx !== -1) this.items[idx].show = false;
                        // remove after transition
                        setTimeout(() => {
                            const j = this.items.findIndex(t => t.id === id);
                            if (j !== -1) this.items.splice(j, 1);
                        }, 300);
                    }, timeout);
                },
                close(index) {
                    if (!this.items[index]) return;
                    this.items[index].show = false;
                    setTimeout(() => {
                        this.items.splice(index, 1);
                    }, 300);
                }
            }
        }

        function kategoriPage() {
            return {
                // data dari server
                data: @json($categoryList),

                // state
                showFilter: false,
                q: '',
                filters: { nama: '' },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},

                // sorting
                sortBy: 'nama',
                sortDir: 'asc',

                init() {
                    // nothing special now
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                    this.openActionId = null;
                    this.showFilter = false;

                    // If server-side session flashes were initialized, they push to the root Alpine component
                    // (see top: session('success') x-init -> $root.push(...))
                },

                // Filtering + Sorting
                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        if (q && !r.nama.toLowerCase().includes(q)) return false;
                        if (this.filters.nama && !r.nama.toLowerCase().includes(this.filters.nama.toLowerCase())) return false;
                        return true;
                    });

                    const sortKey = this.sortBy;
                    const dir = this.sortDir === 'asc' ? 1 : -1;

                    list.sort((a, b) => {
                        const va = (a[sortKey] ?? '').toString().toLowerCase();
                        const vb = (b[sortKey] ?? '').toString().toLowerCase();

                        const an = parseFloat(va);
                        const bn = parseFloat(vb);
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

                goToPage(n) {
                    const t = this.totalPages();
                    if (n < 1) n = 1;
                    if (n > t) n = t;
                    this.currentPage = n;
                    this.openActionId = null;
                    this.showDeleteModal = false;
                },

                prev() { if (this.currentPage > 1) this.currentPage--; this.openActionId = null; },
                next() { if (this.currentPage < this.totalPages()) this.currentPage++; this.openActionId = null; },

                pagesToShow() {
                    const total = this.totalPages(), max = this.maxPageButtons, current = this.currentPage;
                    if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);
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

                toggleActions(id) { this.openActionId = (this.openActionId === id) ? null : id; },

                toggleSort(field) {
                    if (this.sortBy === field) this.sortDir = (this.sortDir === 'asc') ? 'desc' : 'asc';
                    else { this.sortBy = field; this.sortDir = 'asc'; }
                    this.currentPage = 1;
                },

                confirmDelete(item) {
                    this.openActionId = null;
                    this.deleteItem = Object.assign({}, item);
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                },

                async doDelete() {
                    // safety: ensure deleteItem exists
                    if (!this.deleteItem || !this.deleteItem.id) {
                        this.closeDelete();
                        return;
                    }

                    // if no deleteUrl, fallback to local remove
                    if (!this.deleteItem.deleteUrl) {
                        const idx0 = this.data.findIndex(d => d.id === this.deleteItem.id);
                        if (idx0 !== -1) this.data.splice(idx0, 1);
                        if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();
                        // show local success toast
                        document.querySelector('[x-data="toasts()"]')?.__x?.$data?.push?.('success', 'Kategori dihapus');
                        this.closeDelete();
                        return;
                    }

                    try {
                        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                        const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

                        const res = await fetch(this.deleteItem.deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (res.ok) {
                            // remove from local list
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);
                            if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();

                            // try to read JSON message if present
                            let msg = 'Kategori berhasil dihapus.';
                            try {
                                const j = await res.json();
                                if (j && j.message) msg = j.message;
                            } catch (e) { /* ignore json parse */ }

                            // push toast: use the global toasts Alpine root
                            // We access the toasts Alpine root via document; if not found fallback to alert
                            const root = document.querySelector('[x-data="toasts()"]');
                            if (root && root.__x && root.__x.$data && typeof root.__x.$data.push === 'function') {
                                root.__x.$data.push('success', msg);
                            } else {
                                alert(msg);
                            }

                            this.closeDelete();
                        } else {
                            // non-ok status: try parse message
                            let msg = 'Gagal menghapus kategori.';
                            try {
                                const j = await res.json();
                                if (j && j.message) msg = j.message;
                            } catch (e) {}
                            const root = document.querySelector('[x-data="toasts()"]');
                            if (root && root.__x && root.__x.$data && typeof root.__x.$data.push === 'function') {
                                root.__x.$data.push('error', msg);
                            } else {
                                alert(msg);
                            }
                            this.closeDelete();
                        }
                    } catch (err) {
                        console.error(err);
                        const root = document.querySelector('[x-data="toasts()"]');
                        if (root && root.__x && root.__x.$data && typeof root.__x.$data.push === 'function') {
                            root.__x.$data.push('error', 'Terjadi error saat menghapus kategori. Periksa koneksi atau server.');
                        } else {
                            alert('Terjadi error saat menghapus kategori. Periksa koneksi atau server.');
                        }
                        this.closeDelete();
                    }
                },

                resetFilters() {
                    this.filters = { nama: '' };
                    this.q = '';
                    this.currentPage = 1;
                }
            }
        }
    </script>
@endsection
