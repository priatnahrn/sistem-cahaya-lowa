@extends('layouts.app')

@section('title', 'Pembelian')

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

    <div id="pembelian-page" x-data="pembelianPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('pembelian.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Pembelian Baru
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari Item, Supplier, No Faktur..." x-model="q"
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

        {{-- FILTER PANEL --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Filter No Faktur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" placeholder="Cari No Faktur..." x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Supplier --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <input type="text" placeholder="Cari Supplier..." x-model="filters.supplier"
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

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Pilih Status</option>
                        <option value="paid">Lunas</option>
                        <option value="unpaid">Belum Lunas</option>
                        <option value="return">Retur</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> pembelian
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
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('supplier')">
                                Supplier
                                <i class="fa-solid" :class="sortIcon('supplier')"></i>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total')">
                                Total
                                <i class="fa-solid" :class="sortIcon('total')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('status')">
                                Status
                                <i class="fa-solid" :class="sortIcon('status')"></i>
                            </th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200 text-center">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 font-medium" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="fmtTanggal(r.tanggal)"></td>
                                <td class="px-4 py-3 text-green-600 font-medium whitespace-normal break-words leading-snug max-w-[220px]">
                                    <a :href="r.url" class="hover:underline hover:text-[#2e3e6a] transition block" x-text="r.supplier"></a>
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
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="7" class="px-4 py-8">
                                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-2"></i>
                                <p class="text-slate-400">Tidak ada data pembelian.</p>
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
                <div class="bg-gradient-to-r from-red-50 to-rose-50 border-b border-red-100 px-5 py-4 flex items-center justify-between rounded-t-2xl">
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
                        Apakah Anda yakin ingin menghapus pembelian
                        <span class="font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded"
                            x-text="deleteItem.no_faktur"></span>
                        dari
                        <span class="font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded"
                            x-text="deleteItem.supplier"></span>?
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

        {{-- ✅ Floating dropdown portal --}}
        <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown" data-dropdown
            class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
            :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">
            <button @click="window.location = dropdownData.url"
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-eye text-blue-500"></i> Detail
            </button>
            <button @click="confirmDelete(dropdownData)"
                class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600">
                <i class="fa-solid fa-trash"></i> Hapus
            </button>
        </div>

    </div>

    @php
        use Carbon\Carbon;

        $pembeliansJson = $pembelians
            ->map(function ($p) {
                if ($p->tanggal) {
                    $tanggal = Carbon::parse($p->tanggal)->timezone('Asia/Singapore');
                    $jam = $p->created_at
                        ? Carbon::parse($p->created_at)->timezone('Asia/Singapore')->format('H:i')
                        : '00:00';
                    $tanggalFinal = $tanggal->format('Y-m-d') . ' ' . $jam . ':00';
                } else {
                    $tanggalFinal = $p->created_at
                        ? Carbon::parse($p->created_at)->timezone('Asia/Singapore')->format('Y-m-d H:i:s')
                        : null;
                }

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->no_faktur,
                    'tanggal' => $tanggalFinal,
                    'supplier' => optional($p->supplier)->nama_supplier ?? '-',
                    'total' => (float) ($p->total ?? 0),
                    'status' => $p->status ?? 'draft',
                    'url' => route('pembelian.show', $p->id),
                    'items' => $p->items
                        ->map(fn($it) => optional($it->item)->nama_item ?? ($it->item_id ?? ''))
                        ->filter()
                        ->implode(', '),
                ];
            })
            ->values()
            ->toArray();
    @endphp

    <script>
        function pembelianPage() {
            return {
                showFilter: false,
                q: '',
                filters: {
                    no_faktur: '',
                    supplier: '',
                    tanggal: '',
                    status: ''
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
                deleteItem: {},

                data: @json($pembeliansJson),
                sortBy: 'tanggal',
                sortDir: 'desc',

                init() {
                    window.addEventListener('beforeunload', () => this.closeDropdown());
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
                        if (q && !(`${r.no_faktur} ${r.supplier} ${r.items}`.toLowerCase().includes(q)))
                            return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur.toLowerCase()))
                            return false;
                        if (this.filters.supplier && !r.supplier.toLowerCase().includes(this.filters.supplier.toLowerCase()))
                            return false;
                        if (this.filters.tanggal && r.tanggal && r.tanggal.split(' ')[0] !== this.filters.tanggal)
                            return false;
                        if (this.filters.status && r.status !== this.filters.status)
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

                // --- FILTER HELPERS ---
                hasActiveFilters() {
                    return (
                        this.filters.no_faktur ||
                        this.filters.supplier ||
                        this.filters.tanggal ||
                        this.filters.status
                    );
                },

                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.no_faktur) count++;
                    if (this.filters.supplier) count++;
                    if (this.filters.tanggal) count++;
                    if (this.filters.status) count++;
                    return count;
                },

                resetFilters() {
                    this.filters = {
                        no_faktur: '',
                        supplier: '',
                        tanggal: '',
                        status: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                // --- DROPDOWN FLOATING FIX ---
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
                    if (st === 'paid') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'unpaid') return 'bg-amber-50 text-amber-700 border border-amber-200';
                    if (st === 'return') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    return 'bg-slate-50 text-slate-700 border border-slate-200';
                },

                dotClass(st) {
                    if (st === 'paid') return 'bg-green-500';
                    if (st === 'unpaid') return 'bg-amber-500';
                    if (st === 'return') return 'bg-rose-500';
                    return 'bg-slate-500';
                },

                statusLabel(st) {
                    if (st === 'paid') return 'Lunas';
                    if (st === 'unpaid') return 'Belum Lunas';
                    if (st === 'return') return 'Retur';
                    return 'Draft';
                },

                // --- DELETE ---
                confirmDelete(item) {
                    if (!item) return;
                    this.closeDropdown();
                    this.deleteItem = { ...item };
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                },

                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const url = `/pembelian/${this.deleteItem.id}/delete`;

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
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);

                            this.showNotification('success', result.message || 'Data berhasil dihapus');

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
                    el.className = `fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-md border shadow ${bg}`;
                    el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;

                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3500);
                },
            };
        }
    </script>
@endsection