@extends('layouts.app')

@section('title', 'Daftar Retur Pembelian')

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

    <div x-data="returPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('retur-pembelian.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Retur
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari retur..." x-model="q"
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

        {{-- FILTER PANEL --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                {{-- Filter Nomor Retur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Retur</label>
                    <input type="text" placeholder="Cari nomor retur..." x-model="filters.nomor_retur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Supplier --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <input type="text" placeholder="Cari supplier..." x-model="filters.supplier"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua</option>
                        <option value="pending">Pending</option>
                        <option value="taken">Taken</option>
                        <option value="refund">Refund</option>
                    </select>
                </div>

                {{-- Filter Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="filters.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> retur
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
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('nomor_retur')">
                                Nomor Retur
                                <i class="fa-solid"
                                    :class="sortBy === 'nomor_retur' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('tanggal')">
                                Tanggal
                                <i class="fa-solid"
                                    :class="sortBy === 'tanggal' ? (sortDir==='asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2'): 'fa-sort ml-2'"></i>
                            </th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 font-mono" x-text="r.nomor_retur"></td>
                                <td class="px-4 py-3" x-text="r.tanggal"></td>
                                <td class="px-4 py-3" x-text="r.supplier"></td>
                                <td class="px-4 py-3" x-text="formatCurrency(r.total)"></td>
                                <td class="px-4 py-3">
                                    <span :class="statusBadge(r.status)" class="px-2 py-1 rounded text-xs"
                                        x-text="statusLabel(r.status)"></span>
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
                                                <template x-if="r.status === 'pending' || r.status === 'taken' || r.status === 'refund'">
                                                    <button type="button" @click="confirmDelete(r)"
                                                        class="w-full text-left px-4 py-2 text-red-500 hover:bg-slate-50">
                                                        <i class="fa-solid fa-trash mr-2"></i> Hapus
                                                    </button>
                                                </template>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="7" class="px-4 py-6">Tidak ada data retur pembelian.</td>
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
                        Apakah Anda yakin ingin menghapus retur
                        <span class="font-semibold" x-text="deleteItem.nomor_retur"></span>?
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
        $retursJson = $returs
            ->map(
                fn($r) => [
                    'id' => $r->id,
                    'nomor_retur' => $r->no_retur,
                    'tanggal' => $r->tanggal->format('d/m/Y'),
                    'supplier' => optional($r->pembelian->supplier)->nama_supplier,
                    'total' => $r->total,
                    'status' => $r->status,
                    'url' => route('retur-pembelian.show', $r->id),
                    
                ],
            )
            ->toArray();
    @endphp

    <script>
        function returPage() {
            return {
                data: @json($retursJson),
                q: '',
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},

                sortBy: 'tanggal',
                sortDir: 'desc',

                init() {},

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        if (q && !(`${r.nomor_retur} ${r.supplier}`.toLowerCase().includes(q))) return false;
                        return true;
                    });

                    const sortKey = this.sortBy;
                    const dir = this.sortDir === 'asc' ? 1 : -1;

                    list.sort((a, b) => {
                        const va = (a[sortKey] ?? '').toString().toLowerCase();
                        const vb = (b[sortKey] ?? '').toString().toLowerCase();
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

                    fetch(`/retur-pembelian/${this.deleteItem.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            }
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal hapus retur');
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
                                    message: 'Retur berhasil dihapus.'
                                }
                            }));
                        })
                        .catch(err => {
                            console.error(err);
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    type: 'error',
                                    message: 'Terjadi kesalahan saat menghapus retur.'
                                }
                            }));
                        });
                },

                statusLabel(status) {
                    switch (status) {
                        case 'pending':
                            return 'Barang Masih Ada';
                        case 'taken':
                            return 'Barang Diambil Sales';
                        case 'refund':
                            return 'Pengembalian Uang Selesai';
                        default:
                            return status;
                    }
                },
                statusBadge(status) {
                    switch (status) {
                        case 'pending':
                            return 'bg-yellow-100 text-yellow-700';
                        case 'taken':
                            return 'bg-blue-100 text-blue-700';
                        case 'refund':
                            return 'bg-green-100 text-green-700';
                        default:
                            return 'bg-gray-100 text-gray-700';
                    }
                },
                formatCurrency(value) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(value);
                },
                filters: {
                    nomor_retur: '',
                    supplier: '',
                    status: '',
                    tanggal: ''
                },
                showFilter: false,

                // hitung filter aktif
                hasActiveFilters() {
                    return this.filters.nomor_retur || this.filters.supplier || this.filters.status || this.filters.tanggal;
                },
                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.nomor_retur) count++;
                    if (this.filters.supplier) count++;
                    if (this.filters.status) count++;
                    if (this.filters.tanggal) count++;
                    return count;
                },

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        // search global
                        if (q && !(`${r.nomor_retur} ${r.supplier}`.toLowerCase().includes(q))) return false;

                        // filter nomor retur
                        if (this.filters.nomor_retur && !r.nomor_retur.toLowerCase().includes(this.filters
                                .nomor_retur.toLowerCase())) return false;

                        // filter supplier
                        if (this.filters.supplier && !r.supplier.toLowerCase().includes(this.filters.supplier
                                .toLowerCase())) return false;

                        // filter status
                        if (this.filters.status && r.status !== this.filters.status) return false;

                        // filter tanggal (simple exact match)
                        if (this.filters.tanggal && r.tanggal !== this.formatDate(this.filters.tanggal))
                        return false;

                        return true;
                    });

                    // sorting
                    const sortKey = this.sortBy;
                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => (a[sortKey] ?? '').toString().localeCompare((b[sortKey] ?? '').toString()) * dir);

                    return list;
                },

                resetFilters() {
                    this.filters = {
                        nomor_retur: '',
                        supplier: '',
                        status: '',
                        tanggal: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                formatDate(date) {
                    // convert YYYY-MM-DD ke dd/mm/YYYY
                    const d = new Date(date);
                    return d.toLocaleDateString('id-ID');
                },
            }
        }
    </script>
@endsection
