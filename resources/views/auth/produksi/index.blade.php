@extends('layouts.app')

@section('title', 'Daftar Produksi')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="produksiPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-end gap-3">


            {{-- RIGHT: Search & Filter --}}
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i
                        class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    <input type="text" placeholder="Cari No Produksi / Pelanggan" x-model="q"
                        class="w-72 pl-3 pr-10 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                    focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#2e3e6a] hover:text-white"
                    :class="{ 'bg-[#344579] text-white': hasActiveFilters() }">
                    <i class="fa-solid fa-sliders"></i>
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- FILTER PANEL --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Filter No Produksi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No. Produksi</label>
                    <input type="text" placeholder="Cari No Produksi" x-model="filters.no_produksi"
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

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Produksi</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                    focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">Sedang Diproduksi</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> produksi
                </div>
                <button @click="resetFilters()"
                    class="px-4 py-2 rounded-lg border border-slate-200 bg-red-500 text-white hover:bg-red-600">
                    <i class="fa-solid fa-rotate-left mr-2"></i> Reset
                </button>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-visible relative">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-center text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>

                            <!-- Kolom yang bisa di-sort -->
                            <th class="px-4 py-3 cursor-pointer select-none" @click="toggleSort('no_produksi')">
                                No Produksi
                                <i class="fa-solid ml-1" :class="sortIcon('no_produksi')"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer select-none" @click="toggleSort('tanggal_penjualan')">
                                Tanggal Penjualan
                                <i class="fa-solid ml-1" :class="sortIcon('tanggal_penjualan')"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer select-none" @click="toggleSort('pelanggan')">
                                Pelanggan
                                <i class="fa-solid ml-1" :class="sortIcon('pelanggan')"></i>
                            </th>

                            <th class="px-4 py-3 cursor-pointer select-none" @click="toggleSort('status')">
                                Status
                                <i class="fa-solid ml-1" :class="sortIcon('status')"></i>
                            </th>

                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="text-center hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 font-medium text-[#344579]" x-text="r.no_produksi"></td>
                                <td class="px-4 py-3" x-text="r.tanggal_penjualan">
                                </td>
                                <a href="dropdownData.url">
                                    <td class="px-4 py-3 text-green-600" x-text="r.pelanggan"> </td>
                                </a>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeStatus(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotStatus(r.status)"></span>
                                        <span x-text="statusLabel(r.status)"></span>
                                    </span>
                                </td>
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="6" class="px-4 py-6">Tidak ada data produksi.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4 flex justify-center items-center gap-2">
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
            </div>

            {{-- FLOATING DROPDOWN --}}
            <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right data-dropdown
                class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-xl z-[9999]"
                :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`" @click.stop>
                {{-- Detail Button: Semua user dengan produksi.view bisa akses --}}
                <a :href="dropdownData.url"
                    class="block text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700">
                    <i class="fa-solid fa-eye text-blue-500"></i> Detail
                </a>
            </div>

        </div>
    </div>

    @php
        $produksiJson = $produksis
            ->map(function ($p) {
                $pel = $p->penjualan?->pelanggan;
                return [
                    'id' => $p->id,
                    'no_produksi' => $p->no_produksi,
                    'pelanggan' => $pel?->nama_pelanggan ?? '-',
                    'status' => $p->status,
                    'tanggal_penjualan' => \Carbon\Carbon::parse($p->penjualan?->tanggal)
                        ->timezone('Asia/Makassar')
                        ->format('d-m-Y H:i'),
                    'url' => route('produksi.show', $p->id),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function produksiPage() {
            return {
                data: @json($produksiJson),
                q: '',
                filters: {
                    no_produksi: '',
                    pelanggan: '',
                    status: ''
                },
                showFilter: false,
                pageSize: 10,
                currentPage: 1,
                sortBy: 'no_produksi',
                sortDir: 'asc',
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                canUpdate: @json(Auth::user()?->can('produksi.update') ?? false),

                init() {
                    console.log('📦 Data Produksi:', this.data);
                },

                // Sorting
                toggleSort(field) {
                    if (this.sortBy === field) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortDir = 'asc';
                    }
                },
                sortIcon(field) {
                    if (this.sortBy !== field) return 'fa-sort text-slate-400';
                    return this.sortDir === 'asc' ? 'fa-sort-up text-[#344579]' : 'fa-sort-down text-[#344579]';
                },

                filteredList() {
                    const q = this.q.toLowerCase();

                    let filtered = this.data.filter(r => {
                        if (q && !(`${r.no_produksi} ${r.pelanggan}`.toLowerCase().includes(q))) return false;
                        if (this.filters.no_produksi && !r.no_produksi.toLowerCase().includes(this.filters
                                .no_produksi.toLowerCase())) return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan
                                .toLowerCase())) return false;
                        if (this.filters.status && r.status !== this.filters.status) return false;
                        return true;
                    });

                    // Sorting dinamis berdasarkan kolom
                    filtered.sort((a, b) => {
                        const dir = this.sortDir === 'asc' ? 1 : -1;
                        const field = this.sortBy;

                        // Jika kolom tanggal_penjualan → bandingkan sebagai Date
                        if (field === 'tanggal_penjualan') {
                            const dateA = new Date(a[field]);
                            const dateB = new Date(b[field]);
                            return (dateA - dateB) * dir;
                        }

                        // Kolom lainnya → bandingkan string secara natural
                        const valA = (a[field] || '').toString().toLowerCase();
                        const valB = (b[field] || '').toString().toLowerCase();

                        if (valA < valB) return -1 * dir;
                        if (valA > valB) return 1 * dir;
                        return 0;
                    });

                    return filtered;
                },

                filteredTotal() {
                    return this.filteredList().length
                },

                totalPages() {
                    return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize))
                },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },
                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++
                },
                prev() {
                    if (this.currentPage > 1) this.currentPage--
                },
                goToPage(p) {
                    this.currentPage = p
                },
                pagesToShow() {
                    const total = this.totalPages(),
                        max = 7,
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

                hasActiveFilters() {
                    return Object.values(this.filters).some(v => v);
                },
                activeFiltersCount() {
                    return Object.values(this.filters).filter(v => v).length;
                },
                resetFilters() {
                    this.filters = {
                        no_produksi: '',
                        pelanggan: '',
                        status: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                badgeStatus(st) {
                    if (st === 'pending') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'in_progress') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'completed') return 'bg-green-50 text-green-700 border border-green-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },
                dotStatus(st) {
                    if (st === 'pending') return 'bg-orange-500';
                    if (st === 'in_progress') return 'bg-blue-500';
                    if (st === 'completed') return 'bg-green-500';
                    return 'bg-slate-500';
                },
                statusLabel(st) {
                    if (st === 'pending') return 'Pending';
                    if (st === 'in_progress') return 'Sedang Diproduksi';
                    if (st === 'completed') return 'Selesai';
                    return '-';
                },

                openDropdown(row, event) {
                    event.stopPropagation();
                    this.dropdownData = row;

                    const buttonRect = event.currentTarget.getBoundingClientRect();
                    const containerRect = event.currentTarget.closest('table').getBoundingClientRect();

                    const top = buttonRect.bottom - containerRect.top + window.scrollY + 4;
                    const left = buttonRect.right - containerRect.left - 180 + window.scrollX;

                    this.dropdownPos = {
                        top: `${top}px`,
                        left: `${left}px`
                    };

                    this.dropdownVisible = true;

                    document.addEventListener('click', this.handleOutsideClick);
                },
                handleOutsideClick: (e) => {
                    const dropdown = document.querySelector('[data-dropdown]');
                    const toggle = document.querySelector('[data-dropdown-open-button]');
                    if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
                        const vm = Alpine.$data(document.querySelector('[x-data]'));
                        vm.dropdownVisible = false;
                        document.removeEventListener('click', vm.handleOutsideClick);
                    }
                },
                closeDropdown() {
                    this.dropdownVisible = false;
                    document.removeEventListener('click', this.handleOutsideClick);
                },

                async updateStatus(id, status) {
                    if (!this.canUpdate) {
                        alert('Anda tidak memiliki izin untuk mengubah status produksi.');
                        return;
                    }

                    const token = document.querySelector('meta[name="csrf-token"]').content;
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
                        if (!res.ok) throw new Error('Gagal memperbarui status');
                        const result = await res.json();
                        const idx = this.data.findIndex(d => d.id === id);
                        if (idx !== -1) this.data[idx].status = status;
                        this.closeDropdown();
                        alert(result.message || 'Status berhasil diperbarui');
                    } catch (err) {
                        console.error(err);
                        alert('Terjadi kesalahan saat memperbarui status produksi.');
                    }
                }
            }
        }
    </script>
@endsection
