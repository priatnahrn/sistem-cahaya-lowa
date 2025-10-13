@extends('layouts.app')

@section('title', 'Penjualan Cepat')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TOAST --}}
    <div class="fixed top-6 right-6 space-y-3 z-[9999] w-80">
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
    </div>

    <div x-data="penjualanCepatPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan-cepat.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-bolt"></i> Tambah Penjualan Cepat
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari No Faktur, Pelanggan, Item..." x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                            focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#344579] hover:text-white"
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
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" placeholder="Cari No Faktur..." x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nama Pelanggan</label>
                    <input type="text" placeholder="Cari Pelanggan..." x-model="filters.pelanggan"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="filters.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Bayar</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua</option>
                        <option value="lunas">Lunas</option>
                        <option value="belum">Belum Lunas</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> penjualan
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset
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
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_faktur')">No Faktur <i
                                    class="fa-solid" :class="sortIcon('no_faktur')"></i></th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('tanggal')">Tanggal <i class="fa-solid"
                                    :class="sortIcon('tanggal')"></i></th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('pelanggan')">Pelanggan <i
                                    class="fa-solid" :class="sortIcon('pelanggan')"></i></th>
                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total')">Total <i
                                    class="fa-solid" :class="sortIcon('total')"></i></th>
                            <th class="px-4 py-3">Status Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 font-medium" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="fmtTanggal(r.tanggal)"></td>
                                <td class="px-4 py-3" x-text="r.pelanggan"></td>
                                <td class="px-4 py-3 text-right font-semibold" x-text="formatRupiah(r.total)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeClass(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                        <span x-text="statusLabel(r.status)"></span>
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="6" class="px-4 py-6">Tidak ada data penjualan cepat.</td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <!-- ✅ Floating dropdown untuk aksi -->
            <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown" data-dropdown
                class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
                :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">

                <a :href="`/penjualan-cepat/${dropdownData.id}`"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700">
                    <i class="fa-solid fa-eye text-blue-500"></i> Detail
                </a>





                <!-- Tombol Bayar (hanya jika belum lunas dan bukan pending) -->
                <button @click="!['lunas','pending'].includes(dropdownData.status) ? openBayar(dropdownData) : null"
                    :disabled="['lunas', 'pending'].includes(dropdownData.status)"
                    class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 rounded transition"
                    :class="{
                        'text-slate-400 cursor-not-allowed bg-slate-50': ['lunas', 'pending'].includes(dropdownData
                            .status),
                        'text-slate-700 hover:bg-slate-50': !['lunas', 'pending'].includes(dropdownData.status)
                    }">
                    <i class="fa-solid fa-money-bill"
                        :class="['lunas', 'pending'].includes(dropdownData.status) ? 'text-slate-400' : 'text-green-500'"></i>
                    <span>Bayar</span>
                </button>

                <!-- Tombol Print (hanya jika lunas) -->
                <button @click="dropdownData.status === 'lunas' ? openPrintModal(dropdownData) : null"
                    :disabled="dropdownData.status !== 'lunas'"
                    class="w-full text-left px-4 py-2 text-sm flex items-center gap-2 rounded transition"
                    :class="{
                        'text-slate-400 cursor-not-allowed bg-slate-50': dropdownData.status !== 'lunas',
                        'text-slate-700 hover:bg-slate-50': dropdownData.status === 'lunas'
                    }">
                    <i class="fa-solid fa-print"
                        :class="dropdownData.status === 'lunas' ? 'text-green-500' : 'text-slate-400'"></i>
                    <span>Print</span>
                </button>

                <!-- Tombol Hapus -->
                <button @click="confirmDelete(dropdownData)"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </div>


            {{-- PAGINATION --}}
            <div class="px-6 py-4">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-slate-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-slate-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 border rounded hover:bg-[#344579] hover:text-white"></button>
                            <span x-show="p==='...'" class="px-3 text-slate-500">...</span>
                        </span>
                    </template>
                    <button @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-slate-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-slate-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>

    @php
        use Carbon\Carbon;

        $penjualansJson = collect($penjualanCepat)
            ->filter(fn($p) => str_starts_with($p->no_faktur, 'JC'))
            ->map(function ($p) {
                $tanggal = $p->tanggal
                    ? Carbon::parse($p->tanggal)->timezone('Asia/Singapore')->format('Y-m-d H:i:s')
                    : null;

                // Tentukan status tampilannya berdasarkan is_draft & status_bayar
                if ($p->is_draft == 1) {
                    $status = 'pending';
                } elseif ($p->status_bayar === 'paid') {
                    $status = 'lunas';
                } else {
                    $status = 'belum';
                }

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->no_faktur,
                    'tanggal' => $tanggal,
                    'pelanggan' => optional($p->pelanggan)->nama_pelanggan ?? 'Customer',
                    'total' => (float) $p->total ?? 0,
                    'status' => $status,
                ];
            })
            ->values()
            ->toArray();
    @endphp


    <script>
        function penjualanCepatPage() {
            return {
                showFilter: false,
                q: '',
                filters: {
                    no_faktur: '',
                    pelanggan: '',
                    tanggal: '',
                    status: ''
                },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                sortBy: 'tanggal',
                sortDir: 'desc',
                data: @json($penjualansJson),

                openActionId: null,
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                _outsideClickHandler: null,

                // --- FILTER + PAGINATION LOGIC ---
                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    return this.data.filter(r => {
                        if (q && !(`${r.no_faktur} ${r.pelanggan}`.toLowerCase().includes(q))) return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur
                                .toLowerCase())) return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan
                                .toLowerCase())) return false;
                        if (this.filters.tanggal && r.tanggal && r.tanggal.split(' ')[0] !== this.filters.tanggal)
                            return false;
                        if (this.filters.status && r.status !== this.filters.status) return false;
                        return true;
                    }).sort((a, b) => {
                        const dir = this.sortDir === 'asc' ? 1 : -1;
                        if (this.sortBy === 'tanggal') return (new Date(a.tanggal) - new Date(b.tanggal)) * dir;
                        return a[this.sortBy].toString().localeCompare(b[this.sortBy].toString()) * dir;
                    });
                },
                filteredTotal() {
                    return this.filteredList().length;
                },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },
                totalPages() {
                    return Math.ceil(this.filteredTotal() / this.pageSize) || 1;
                },
                goToPage(p) {
                    this.currentPage = Math.min(Math.max(1, p), this.totalPages());
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

                // --- UTILITIES ---
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
                    return `${d.getDate().toString().padStart(2, '0')}-${(d.getMonth()+1).toString().padStart(2,'0')}-${d.getFullYear()}, ${d.getHours().toString().padStart(2,'0')}:${d.getMinutes().toString().padStart(2,'0')}`;
                },
                sortIcon(f) {
                    if (this.sortBy !== f) return 'fa-sort ml-2';
                    return this.sortDir === 'asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2';
                },
                toggleSort(f) {
                    if (this.sortBy === f) this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    else {
                        this.sortBy = f;
                        this.sortDir = 'asc';
                    }
                },
                badgeClass(st) {
                    if (st === 'lunas') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'belum') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    if (st === 'pending') return 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                    return 'bg-slate-50 text-slate-700';
                },
                dotClass(st) {
                    if (st === 'lunas') return 'bg-green-500';
                    if (st === 'belum') return 'bg-rose-500';
                    if (st === 'pending') return 'bg-yellow-500';
                    return 'bg-slate-400';
                },
                statusLabel(st) {
                    if (st === 'lunas') return 'Lunas';
                    if (st === 'belum') return 'Belum Lunas';
                    if (st === 'pending') return 'Pending';
                    return '-';
                },

                hasActiveFilters() {
                    return Object.values(this.filters).some(v => v);
                },
                activeFiltersCount() {
                    return Object.values(this.filters).filter(v => v).length;
                },
                resetFilters() {
                    this.filters = {
                        no_faktur: '',
                        pelanggan: '',
                        tanggal: '',
                        status: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },
                init() {},

                openDropdown(row, event) {
                    if (this.openActionId === row.id) {
                        this.closeDropdown();
                        return;
                    }

                    this.openActionId = row.id;
                    this.dropdownData = row;

                    const rect = event.currentTarget.getBoundingClientRect();
                    const dropdownHeight = 120;
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
                },

                handleOutsideClick(e) {
                    const dropdownEl = document.querySelector('[data-dropdown]');
                    const isInsideDropdown = dropdownEl && dropdownEl.contains(e.target);
                    const isTriggerButton = !!e.target.closest('[data-dropdown-open-button]');
                    if (!isInsideDropdown && !isTriggerButton) this.closeDropdown();
                },

                closeDropdown() {
                    this.dropdownVisible = false;
                    this.openActionId = null;
                    this.dropdownData = {};
                    if (this._outsideClickHandler) {
                        document.removeEventListener('click', this._outsideClickHandler);
                        this._outsideClickHandler = null;
                    }
                },

            }
        }
    </script>
@endsection
