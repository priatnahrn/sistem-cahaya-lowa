@extends('layouts.app')

@section('title', 'Penjualan')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
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

    <div x-data="penjualanPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#3a8f70] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Penjualan Baru
                </a>

                <a href="{{ route('penjualan.index', array_merge(request()->all(), ['export' => 1])) }}"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 inline-flex items-center gap-2">
                    <i class="fa-solid fa-file-export mr-2"></i> Export
                </a>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Search (No Faktur, Pelanggan, Item...)" x-model="q"
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
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm text-slate-500 mb-1">No Faktur</label>
                <input type="text" placeholder="Cari No Faktur" x-model="filters.no_faktur"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div>
                <label class="block text-sm text-slate-500 mb-1">Nama Pelanggan</label>
                <input type="text" placeholder="Cari Pelanggan" x-model="filters.pelanggan"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div>
                <label class="block text-sm text-slate-500 mb-1">Tanggal</label>
                <input type="date" x-model="filters.tanggal"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div>
                <label class="block text-sm text-slate-500 mb-1">Status Bayar</label>
                <select x-model="filters.status"
                    class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
                    <option value="">Semua</option>
                    <option value="lunas">Lunas</option>
                    <option value="belum">Belum Lunas</option>
                    <option value="retur">Retur</option>
                </select>
            </div>

            <div class="flex items-end">
                <button type="button" @click="resetFilters()"
                    class="w-full px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
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
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_faktur')">No Faktur <i
                                    class="fa-solid" :class="sortIcon('no_faktur')"></i></th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('tanggal')">Tanggal <i class="fa-solid"
                                    :class="sortIcon('tanggal')"></i></th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('pelanggan')">Pelanggan <i
                                    class="fa-solid" :class="sortIcon('pelanggan')"></i></th>
                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total')">Total <i
                                    class="fa-solid" :class="sortIcon('total')"></i></th>
                            <th class="px-4 py-3">Status Bayar</th>
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
                                <td class="px-4 py-3 text-right" x-text="formatRupiah(r.total)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeClass(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                        <span x-text="statusLabel(r.status)"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <template x-if="r.status_pengiriman !== '-'">
                                        <span
                                            class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                            :class="badgeKirim(r.status_pengiriman)">
                                            <span class="w-2 h-2 rounded-full"
                                                :class="dotKirim(r.status_pengiriman)"></span>
                                            <span x-text="r.status_pengiriman"></span>
                                        </span>
                                    </template>
                                    <template x-if="r.status_pengiriman === '-'">
                                        <span class="text-slate-400">-</span>
                                    </template>
                                </td>
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" @click="toggleActions(r.id)"
                                        class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    <div x-cloak x-show="openActionId === r.id" @click.away="openActionId = null"
                                        x-transition
                                        class="absolute right-2 mt-2 w-44 bg-white shadow rounded-md border border-slate-200 z-20">
                                        <ul class="py-1">
                                            <li>
                                                <a :href="r.url"
                                                    class="block px-4 py-2 hover:bg-slate-50 text-left">
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
                            <td colspan="8" class="px-4 py-6">Tidak ada data penjualan.</td>
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
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-slate-50"></button>
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

    {{-- DELETE MODAL --}}
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeDelete()"></div>
        <div x-transition class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/3 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-red-600">Konfirmasi Hapus</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-slate-600">
                    Apakah Anda yakin ingin menghapus penjualan
                    <span class="font-semibold" x-text="deleteItem.no_faktur"></span> untuk
                    <span class="text-green-600" x-text="deleteItem.pelanggan"></span>?
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
        $penjualansJson = $penjualans
            ->map(function ($p) {
                $statusBayarMap = [
                    'paid' => 'lunas',
                    'unpaid' => 'belum',
                    'return' => 'retur',
                ];

                $statusKirimMap = [
                    'perlu_dikirim' => 'Perlu Diantar',
                    'dalam_pengiriman' => 'Dalam Pengiriman',
                    'diterima' => 'Diterima',
                ];

                // format tanggal
                $tanggal =
                    $p->tanggal instanceof \Carbon\Carbon
                        ? $p->tanggal->timezone('Asia/Makassar')->format('Y-m-d H:i:s')
                        : $p->tanggal ?? null;

                // tentukan status pengiriman
                $statusPengiriman = '-';
                if ($p->mode === 'antar') {
                    $statusPengiriman = $p->pengiriman
                        ? $statusKirimMap[$p->pengiriman->status_pengiriman] ?? '-'
                        : 'Perlu Diantar'; // default kalau record pengiriman ada tapi status kosong
                }

                return [
                    'id' => $p->id,
                    'no_faktur' => $p->no_faktur,
                    'tanggal' => $tanggal,
                    'pelanggan' => optional($p->pelanggan)->nama_pelanggan ?? 'Customer',
                    'total' => (float) ($p->total ?? 0),
                    'status' => $statusBayarMap[$p->status_bayar] ?? 'belum',
                    'status_pengiriman' => $statusPengiriman, // 👈 ini yg dipakai
                    'url' => route('penjualan.show', $p->id),
                    'items' => $p->items
                        ->map(fn($it) => optional($it->item)->nama_item ?? ($it->item_id ?? ''))
                        ->filter()
                        ->implode(', '),
                ];
            })
            ->toArray();

    @endphp
    <script>
        function penjualanPage() {
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
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},
                data: @json($penjualansJson),
                sortBy: 'tanggal',
                sortDir: 'desc',

                init() {},

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
                    return `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}, ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                },

                sortIcon(field) {
                    if (this.sortBy !== field) return 'fa-sort ml-2';
                    return this.sortDir === 'asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2';
                },

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    let list = this.data.filter(r => {
                        if (q && !(`${r.no_faktur} ${r.pelanggan} ${r.items}`.toLowerCase().includes(q)))
                            return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur
                                .toLowerCase()))
                            return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan
                                .toLowerCase()))
                            return false;
                        if (this.filters.tanggal && r.tanggal && r.tanggal.split(' ')[0] !== this.filters.tanggal)
                            return false;
                        if (this.filters.status && r.status !== this.filters.status)
                            return false;
                        return true;
                    });

                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => {
                        const va = (a[this.sortBy] ?? '');
                        const vb = (b[this.sortBy] ?? '');

                        // tanggal
                        const aIsDate = !isNaN(Date.parse(va));
                        const bIsDate = !isNaN(Date.parse(vb));
                        if (aIsDate && bIsDate) {
                            return (new Date(va) - new Date(vb)) * dir;
                        }

                        // numeric
                        const aNum = parseFloat(va);
                        const bNum = parseFloat(vb);
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return (aNum - bNum) * dir;
                        }

                        // string
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
                    this.openActionId = null;
                },

                prev() {
                    if (this.currentPage > 1) this.currentPage--;
                    this.openActionId = null;
                },

                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++;
                    this.openActionId = null;
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

                toggleActions(id) {
                    this.openActionId = (this.openActionId === id) ? null : id;
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

                badgeClass(st) {
                    if (st === 'lunas') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'belum') return 'bg-amber-50 text-amber-700 border border-amber-200';
                    if (st === 'retur') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    return 'bg-slate-50 text-slate-700 border border-slate-200';
                },

                dotClass(st) {
                    if (st === 'lunas') return 'bg-green-500';
                    if (st === 'belum') return 'bg-amber-500';
                    if (st === 'retur') return 'bg-rose-500';
                    return 'bg-slate-500';
                },

                statusLabel(st) {
                    if (st === 'lunas') return 'Lunas';
                    if (st === 'belum') return 'Belum Lunas';
                    if (st === 'retur') return 'Retur';
                    return '-';
                },

                badgeKirim(st) {
                    if (st === 'Perlu Diantar') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'Diterima') return 'bg-green-50 text-green-700 border border-green-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },
                dotKirim(st) {
                    if (st === 'Perlu Diantar') return 'bg-orange-500';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-500';
                    if (st === 'Diterima') return 'bg-green-500';
                    return 'bg-slate-500';
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

                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const url = `{{ url('penjualan') }}/${this.deleteItem.id}/delete`;

                    try {
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                            }
                        });

                        if (res.ok) {
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);
                            alert('Data berhasil dihapus');
                        } else {
                            alert('Gagal menghapus data');
                        }
                    } catch (e) {
                        console.error('Delete error', e);
                        alert('Terjadi kesalahan koneksi');
                    } finally {
                        this.closeDelete();
                        if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();
                    }
                }
            }
        }
    </script>
@endsection
