@extends('layouts.app')

@section('title', 'Supplier')

@section('content')
    <style>[x-cloak]{display:none!important;}</style>

    <div x-data="supplierPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('supplier.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Supplier Baru
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari supplier..." x-model="q"
                           class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <button type="button" @click="showFilter=!showFilter"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50"
                        :class="{ 'bg-[#344579] text-white': hasActiveFilters() }">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                    <span x-show="hasActiveFilters()"
                          class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- FILTER --}}
        <div x-show="showFilter" x-collapse x-transition
             class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Supplier</label>
                    <input type="text" placeholder="Cari nama supplier..." x-model="filters.nama"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Telepon</label>
                    <input type="text" placeholder="Cari nomor telepon..." x-model="filters.telp"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Alamat</label>
                    <input type="text" placeholder="Cari alamat..." x-model="filters.alamat"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> supplier
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset
                    </button>
                    <button type="button" @click="showFilter=false"
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
                        <th class="px-4 py-3 cursor-pointer" @click="toggleSort('nama')">
                            Nama Supplier
                            <i class="fa-solid"
                               :class="sortBy === 'nama' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                        </th>
                        <th class="px-4 py-3 cursor-pointer" @click="toggleSort('telp')">
                            Nomor Telepon
                            <i class="fa-solid"
                               :class="sortBy === 'telp' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                        </th>
                        <th class="px-4 py-3 cursor-pointer" @click="toggleSort('alamat')">
                            Alamat
                            <i class="fa-solid"
                               :class="sortBy === 'alamat' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                        </th>
                        <th class="px-2 py-3"></th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="(r, idx) in pagedData()" :key="r.id">
                        <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                            <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                            <td class="px-4 py-3">
                                <a :href="r.url" class="text-green-600 font-normal hover:underline"
                                   x-text="r.nama" @click="openActionId=null"></a>
                            </td>
                            <td class="px-4 py-3" x-text="r.telp"></td>
                            <td class="px-4 py-3" x-text="r.alamat"></td>
                            <td class="px-2 py-3 text-right relative">
                                <button type="button" @click="toggleActions(r.id)"
                                        class="px-2 py-1 rounded hover:bg-slate-100">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div x-cloak x-show="openActionId===r.id" @click.away="openActionId=null" x-transition
                                     class="absolute right-2 mt-2 w-48 bg-white shadow rounded-md border border-slate-200 z-20">
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
                        <td colspan="5" class="px-4 py-6">Tidak ada data supplier.</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4 flex items-center justify-center">
                <nav class="flex items-center gap-2" aria-label="Pagination">
                    <button type="button" @click="goToPage(1)" :disabled="currentPage===1"
                            class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button type="button" @click="prev()" :disabled="currentPage===1"
                            class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                    :class="{ 'bg-[#344579] text-white': currentPage===p }"
                                    class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] hover:text-white cursor-pointer"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>
                    <button type="button" @click="next()" :disabled="currentPage===totalPages()"
                            class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button type="button" @click="goToPage(totalPages())" :disabled="currentPage===totalPages()"
                            class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>

        {{-- DELETE MODAL --}}
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeDelete()"></div>
            <div x-transition class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/3 z-10 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-red-600">Konfirmasi Hapus</h3>
                </div>
                <div class="px-6 py-4">
                    <p class="text-slate-600">
                        Apakah Anda yakin ingin menghapus supplier
                        <span class="font-semibold" x-text="deleteItem.nama"></span>?
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <button type="button" @click="closeDelete()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="button" @click="doDelete()"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $suppliersJson = $suppliers
            ->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama_supplier,
                'telp' => $s->kontak,
                'alamat' => Str::limit($s->alamat ?? '', 60),
                'url' => route('supplier.show', $s->id),
            ])
            ->toArray();
    @endphp

    <script>
        function supplierPage() {
            return {
                showFilter: false,
                q: '',
                filters: { nama: '', telp: '' },
                pageSize: 5,
                currentPage: 1,
                maxPageButtons: 7,
                openActionId: null,
                data: @json($suppliersJson),
                showDeleteModal: false,
                deleteItem: {},
                sortBy: 'nama',
                sortDir: 'asc',

                init() {},

                hasActiveFilters() {
                    return this.filters.nama || this.filters.telp;
                },
                activeFiltersCount() {
                    let c = 0;
                    if (this.filters.nama) c++;
                    if (this.filters.telp) c++;
                    if (this.filters.alamat) c++;
                    return c;
                },

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        if (q && !(`${r.nama} ${r.telp} ${r.alamat}`.toLowerCase().includes(q))) return false;
                        if (this.filters.nama && !r.nama.toLowerCase().includes(this.filters.nama.toLowerCase())) return false;
                        if (this.filters.telp && !r.telp.toLowerCase().includes(this.filters.telp.toLowerCase())) return false;
                        if (this.filters.alamat && !r.alamat.toLowerCase().includes(this.filters.alamat.toLowerCase())) return false;
                        return true;
                    });

                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => {
                        const va = (a[this.sortBy] ?? '').toString().toLowerCase();
                        const vb = (b[this.sortBy] ?? '').toString().toLowerCase();
                        const an = parseFloat(va), bn = parseFloat(vb);
                        if (!isNaN(an) && !isNaN(bn)) return (an - bn) * dir;
                        return va.localeCompare(vb) * dir;
                    });

                    return list;
                },
                filteredTotal() { return this.filteredList().length; },
                totalPages() { return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize)); },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },
                goToPage(n) {
                    this.currentPage = Math.min(Math.max(1, n), this.totalPages());
                    this.openActionId = null;
                    this.showDeleteModal = false;
                },
                prev() { if (this.currentPage > 1) this.currentPage--; },
                next() { if (this.currentPage < this.totalPages()) this.currentPage++; },
                pagesToShow() {
                    const total = this.totalPages(), max = this.maxPageButtons, cur = this.currentPage;
                    if (total <= max) return Array.from({ length: total }, (_, i) => i + 1);
                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, cur - side), right = Math.min(total - 1, cur + side);
                    const pages = [1];
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

                confirmDelete(item) { this.openActionId = null; this.deleteItem = { ...item }; this.showDeleteModal = true; },
                closeDelete() { this.showDeleteModal = false; this.deleteItem = {}; },
                doDelete() {
                    const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                    if (idx !== -1) this.data.splice(idx, 1);
                    if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();
                    this.closeDelete();
                },

                resetFilters() { this.filters = { nama: '', telp: '', alamat: '' }; this.q = ''; this.currentPage = 1; }
            }
        }
    </script>
@endsection
