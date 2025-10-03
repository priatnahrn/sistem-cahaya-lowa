@extends('layouts.app')

@section('title', 'Pengiriman')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="pengirimanPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-end gap-3">
            {{-- Search --}}
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari faktur / pelanggan..." x-model="q"
                        class="w-72 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
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
                {{-- Filter No Faktur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" placeholder="Cari no faktur..." x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Nama Pelanggan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Pelanggan</label>
                    <input type="text" placeholder="Cari nama pelanggan..." x-model="filters.pelanggan"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Pengiriman</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua</option>
                        <option value="Perlu Dikirim">Perlu Dikirim</option>
                        <option value="Dalam Pengiriman">Dalam Pengiriman</option>
                        <option value="Diterima">Diterima</option>
                        <option value="Dibatalkan">Dibatalkan</option>
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
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> pengiriman
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset
                    </button>
                    <button type="button" @click="showFilter = false"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a]">
                        Terapkan
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
                            <th class="px-4 py-3">No Faktur</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Status Pengiriman</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3" x-text="fmtTanggal(r.tanggal)"></td>
                                <td class="px-4 py-3 text-green-600" x-text="r.pelanggan"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeKirim(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotKirim(r.status)"></span>
                                        <span x-text="r.status"></span>
                                    </span>
                                </td>
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
                            <td colspan="6" class="px-4 py-6">Tidak ada data pengiriman.</td>
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
                    Apakah Anda yakin ingin menghapus pengiriman
                    <span class="font-semibold" x-text="deleteItem.no_faktur"></span>
                    untuk <span class="text-green-600" x-text="deleteItem.pelanggan"></span>?
                </p>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" @click="closeDelete()"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" @click="doDelete()"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">Hapus</button>
            </div>
        </div>
    </div>

    @php
        $pengirimanJson = $pengirimans
            ->map(function ($p) {
                $statusMap = [
                    'perlu_dikirim' => 'Perlu Dikirim',
                    'dalam_pengiriman' => 'Dalam Pengiriman',
                    'diterima' => 'Diterima',
                    'dibatalkan' => 'Dibatalkan',
                ];

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->penjualan?->no_faktur ?? '-',
                    'tanggal' => $p->tanggal_pengiriman,
                    'pelanggan' => $p->penjualan?->pelanggan?->nama_pelanggan ?: 'Customer',
                    'status' => $statusMap[$p->status_pengiriman] ?? '-',
                    'url' => route('pengiriman.show', $p->id),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function pengirimanPage() {
            return {
                data: @json($pengirimanJson),
                q: '',
                filters: { no_faktur: '', pelanggan: '', status: '', tanggal: '' },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                showFilter: false,
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},

                init() {},

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    return this.data.filter(r => {
                        if (q && !(`${r.no_faktur} ${r.pelanggan}`.toLowerCase().includes(q))) return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur.toLowerCase())) return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan.toLowerCase())) return false;
                        if (this.filters.status && r.status !== this.filters.status) return false;
                        if (this.filters.tanggal) {
                            const tglRow = r.tanggal ? r.tanggal.substring(0, 10) : '';
                            if (tglRow !== this.filters.tanggal) return false;
                        }
                        return true;
                    });
                },
                filteredTotal() { return this.filteredList().length },
                totalPages() { return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize)) },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },
                goToPage(n) { this.currentPage = Math.min(Math.max(1, n), this.totalPages()) },
                prev() { if (this.currentPage > 1) this.currentPage-- },
                next() { if (this.currentPage < this.totalPages()) this.currentPage++ },
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

                hasActiveFilters() { return this.filters.no_faktur || this.filters.pelanggan || this.filters.status || this.filters.tanggal },
                activeFiltersCount() {
                    return ['no_faktur','pelanggan','status','tanggal'].filter(f => this.filters[f]).length;
                },
                resetFilters() {
                    this.filters = { no_faktur: '', pelanggan: '', status: '', tanggal: '' };
                    this.q = ''; this.currentPage = 1;
                },

                fmtTanggal(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);
                    return isNaN(d) ? iso :
                        `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}, ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                },

                badgeKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'Diterima') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'Dibatalkan') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },
                dotKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-500';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-500';
                    if (st === 'Diterima') return 'bg-green-500';
                    if (st === 'Dibatalkan') return 'bg-rose-500';
                    return 'bg-slate-500';
                },

                toggleActions(id) { this.openActionId = (this.openActionId === id) ? null : id },
                confirmDelete(item) { this.openActionId = null; this.deleteItem = { ...item }; this.showDeleteModal = true },
                closeDelete() { this.showDeleteModal = false; this.deleteItem = {} },
                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const url = `{{ url('pengiriman') }}/${this.deleteItem.id}`;
                    try {
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
                        });
                        if (res.ok) {
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);
                            alert('Data pengiriman berhasil dihapus');
                        } else { alert('Gagal menghapus data') }
                    } catch (e) {
                        console.error(e); alert('Terjadi kesalahan koneksi');
                    } finally {
                        this.closeDelete();
                        if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();
                    }
                }
            }
        }
    </script>
@endsection
