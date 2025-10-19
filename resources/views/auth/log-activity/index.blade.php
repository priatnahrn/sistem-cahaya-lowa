@extends('layouts.app')

@section('title', 'Daftar Log Activity')

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

    <div id="log-activity-page" x-data="logActivityPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-semibold text-slate-800">Daftar Log Activity</h1>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari deskripsi atau user" x-model="q"
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
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                {{-- Filter Activity Type --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tipe Activity</label>
                    <select x-model="filters.activity_type"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua Tipe</option>
                        <option value="login">Login</option>
                        <option value="logout">Logout</option>
                        <option value="create">Create</option>
                        <option value="update">Update</option>
                        <option value="delete">Delete</option>
                        <option value="view">View</option>
                    </select>
                </div>

                {{-- Filter User --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">User</label>
                    <input type="text" placeholder="Cari nama user" x-model="filters.user"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Date From --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Dari Tanggal</label>
                    <input type="date" x-model="filters.date_from"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Date To --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sampai Tanggal</label>
                    <input type="date" x-model="filters.date_to"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter IP Address --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">IP Address</label>
                    <input type="text" placeholder="Cari IP" x-model="filters.ip_address"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> log activity
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
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('created_at')">
                                Waktu
                                <i class="fa-solid" :class="sortIcon('created_at')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('user_name')">
                                User
                                <i class="fa-solid" :class="sortIcon('user_name')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('activity_type')">
                                Tipe Activity
                                <i class="fa-solid" :class="sortIcon('activity_type')"></i>
                            </th>
                            <th class="px-4 py-3">Deskripsi</th>
                           
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200 text-center">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3 text-slate-600" x-text="fmtTanggal(r.created_at)"></td>
                                <td class="px-4 py-3 font-medium text-blue-600" x-text="r.user_name || '-'"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="activityBadgeClass(r.activity_type)">
                                        <span class="w-2 h-2 rounded-full"
                                            :class="activityDotClass(r.activity_type)"></span>
                                        <span x-text="r.activity_type"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-600 max-w-xs truncate" :title="r.description"
                                    x-text="r.description || '-'"></td>
                                

                                
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="7" class="px-4 py-8">
                                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-2"></i>
                                <p class="text-slate-400">Tidak ada data log activity.</p>
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

    </div>

    @php
        $logsJson = $logs
            ->map(function ($log) {
                $createdAt =
                    $log->created_at instanceof \Carbon\Carbon
                        ? $log->created_at->timezone('Asia/Makassar')->format('Y-m-d H:i:s')
                        : $log->created_at ?? null;

                return [
                    'id' => $log->id,
                    'user_name' => optional($log->user)->name ?? 'System',
                    'activity_type' => $log->activity_type,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'created_at' => $createdAt,
                    'show_url' => route('log-activity.show', $log->id),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function logActivityPage() {
            return {
                showFilter: false,
                q: '',
                filters: {
                    activity_type: '',
                    user: '',
                    date_from: '',
                    date_to: '',
                    ip_address: ''
                },

                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,

                data: @json($logsJson),
                sortBy: 'created_at',
                sortDir: 'desc',

                init() {
                    // Initialize if needed
                },

                // --- FORMATTERS ---
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
                        if (q && !(`${r.user_name} ${r.description}`.toLowerCase().includes(q)))
                            return false;
                        if (this.filters.activity_type && r.activity_type !== this.filters.activity_type)
                            return false;
                        if (this.filters.user && !r.user_name.toLowerCase().includes(this.filters.user
                            .toLowerCase()))
                            return false;
                        if (this.filters.ip_address && !r.ip_address.includes(this.filters.ip_address))
                            return false;
                        if (this.filters.date_from && r.created_at && r.created_at.split(' ')[0] < this.filters
                            .date_from)
                            return false;
                        if (this.filters.date_to && r.created_at && r.created_at.split(' ')[0] > this.filters
                            .date_to)
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
                },

                prev() {
                    if (this.currentPage > 1) this.currentPage--;
                },

                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++;
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
                        this.filters.activity_type ||
                        this.filters.user ||
                        this.filters.date_from ||
                        this.filters.date_to ||
                        this.filters.ip_address
                    );
                },

                activeFiltersCount() {
                    let count = 0;
                    if (this.filters.activity_type) count++;
                    if (this.filters.user) count++;
                    if (this.filters.date_from) count++;
                    if (this.filters.date_to) count++;
                    if (this.filters.ip_address) count++;
                    return count;
                },

                resetFilters() {
                    this.filters = {
                        activity_type: '',
                        user: '',
                        date_from: '',
                        date_to: '',
                        ip_address: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
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

                // --- ACTIVITY TYPE STYLING ---
                activityBadgeClass(type) {
                    if (type === 'login') return 'bg-green-50 text-green-700 border border-green-200';
                    if (type === 'logout') return 'bg-slate-100 text-slate-600 border border-slate-200';
                    if (type === 'create') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (type === 'update') return 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                    if (type === 'delete') return 'bg-red-50 text-red-700 border border-red-200';
                    if (type === 'view') return 'bg-purple-50 text-purple-700 border border-purple-200';
                    return 'bg-slate-50 text-slate-700 border border-slate-200';
                },

                activityDotClass(type) {
                    if (type === 'login') return 'bg-green-500';
                    if (type === 'logout') return 'bg-slate-400';
                    if (type === 'create') return 'bg-blue-500';
                    if (type === 'update') return 'bg-yellow-500';
                    if (type === 'delete') return 'bg-red-500';
                    if (type === 'view') return 'bg-purple-500';
                    return 'bg-slate-500';
                }
            };
        }
    </script>

@endsection
