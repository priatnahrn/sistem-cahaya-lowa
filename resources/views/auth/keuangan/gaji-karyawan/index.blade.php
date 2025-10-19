@extends('layouts.app')

@section('title', 'Daftar Gaji Karyawan')

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

    <div id="gaji-page" x-data="gajiPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <button @click="showInputModal = true"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Input Gaji Harian
                </button>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari karyawan..." x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                       focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
            </div>
        </div>

        {{-- CARD GRID --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="(karyawan, idx) in filteredKaryawan()" :key="idx">
                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow duration-200 group">
                    {{-- Card Header --}}
                    <div class="bg-gradient-to-br from-[#344579] to-[#2e3e6a] px-5 py-4 text-white">
                        <div class="flex items-start justify-between mb-3">
                            <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-lg font-bold">
                                <span x-text="karyawan.nama.charAt(0).toUpperCase()"></span>
                            </div>
                            <button @click="quickInput(karyawan.nama)"
                                class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors">
                                <i class="fa-solid fa-plus text-sm"></i>
                            </button>
                        </div>
                        <h3 class="font-semibold text-base mb-1" x-text="karyawan.nama"></h3>
                        <p class="text-xs text-white/70" x-text="`${karyawan.transaksi.length} minggu kerja`"></p>
                    </div>

                    {{-- Card Body --}}
                    <div class="px-5 py-4">
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 mb-1">Saldo Saat Ini</p>
                            <p class="text-2xl font-bold text-green-600" x-text="formatRupiah(karyawan.saldo)"></p>
                        </div>

                        <button @click="openDetail(karyawan)"
                            class="w-full px-4 py-2 rounded-lg bg-slate-50 text-slate-700 hover:bg-slate-100 border border-slate-200 text-sm font-medium transition-colors">
                            <i class="fa-solid fa-receipt mr-2"></i> Lihat Detail Transaksi
                        </button>
                    </div>
                </div>
            </template>

            {{-- Empty State --}}
            <div x-show="filteredKaryawan().length === 0" class="col-span-full">
                <div class="bg-white border border-slate-200 rounded-xl px-6 py-12 text-center">
                    <i class="fa-solid fa-inbox text-6xl text-slate-300 mb-4"></i>
                    <p class="text-slate-400 text-lg">Belum ada data karyawan</p>
                </div>
            </div>
        </div>

        {{-- MODAL DETAIL TRANSAKSI --}}
        <div x-cloak x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div x-show="showDetailModal" x-transition.opacity class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDetail()"></div>
            
            <div x-show="showDetailModal" x-transition class="relative bg-white w-full max-w-4xl rounded-2xl shadow-2xl border border-slate-200 overflow-hidden max-h-[90vh] flex flex-col">
                {{-- Modal Header --}}
                <div class="bg-gradient-to-r from-[#344579] to-[#2e3e6a] px-6 py-4 flex items-center justify-between text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center text-xl font-bold">
                            <span x-text="selectedKaryawan?.nama?.charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold" x-text="selectedKaryawan?.nama"></h3>
                            <p class="text-sm text-white/70">Detail Transaksi Mingguan</p>
                        </div>
                    </div>
                    <button @click="closeDetail()" class="w-10 h-10 rounded-full hover:bg-white/20 flex items-center justify-center transition-colors">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="flex-1 overflow-y-auto p-6">
                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                            <p class="text-xs text-green-700 mb-1">Saldo Saat Ini</p>
                            <p class="text-xl font-bold text-green-700" x-text="formatRupiah(selectedKaryawan?.saldo)"></p>
                        </div>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                            <p class="text-xs text-blue-700 mb-1">Total Minggu</p>
                            <p class="text-xl font-bold text-blue-700" x-text="selectedKaryawan?.transaksi?.length || 0"></p>
                        </div>
                        <div class="bg-purple-50 border border-purple-200 rounded-lg px-4 py-3">
                            <p class="text-xs text-purple-700 mb-1">Rata-rata/Minggu</p>
                            <p class="text-xl font-bold text-purple-700" x-text="formatRupiah(calculateAverage())"></p>
                        </div>
                    </div>

                    {{-- Weekly Transactions --}}
                    <div class="space-y-4">
                        <template x-for="(week, wIdx) in selectedKaryawan?.transaksi || []" :key="wIdx">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl overflow-hidden">
                                {{-- Week Header --}}
                                <div class="bg-white border-b border-slate-200 px-5 py-3 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-calendar-week text-[#344579]"></i>
                                        <div>
                                            <h4 class="font-semibold text-slate-800" x-text="week.label"></h4>
                                            <p class="text-xs text-slate-500" x-text="`${week.items?.length || 0} hari kerja`"></p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-slate-500">Total Minggu Ini</p>
                                        <p class="font-bold text-green-600" x-text="formatRupiah(weekTotal(week.items))"></p>
                                    </div>
                                </div>

                                {{-- Daily Transactions --}}
                                <div class="p-4">
                                    <div class="space-y-2">
                                        <template x-for="(item, iIdx) in week.items" :key="iIdx">
                                            <div class="bg-white border border-slate-200 rounded-lg px-4 py-3 flex items-center justify-between hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-4">
                                                    <div class="w-12 h-12 rounded-lg bg-blue-50 flex items-center justify-center">
                                                        <i class="fa-solid fa-calendar-day text-blue-600"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-slate-800" x-text="fmtTanggal(item.tanggal)"></p>
                                                        <p class="text-sm text-slate-500" x-text="item.keterangan || '-'"></p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-4">
                                                    <div class="text-right">
                                                        <p class="text-sm text-slate-500">Upah</p>
                                                        <p class="font-semibold text-green-600" x-text="formatRupiah(item.upah_harian)"></p>
                                                    </div>
                                                    <div class="text-right" x-show="item.utang > 0">
                                                        <p class="text-sm text-slate-500">Kasbon</p>
                                                        <p class="font-semibold text-red-600" x-text="formatRupiah(item.utang)"></p>
                                                    </div>
                                                    <div class="flex gap-2">
                                                        <button @click="confirmDelete(item)"
                                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition-colors">
                                                            <i class="fa-solid fa-trash text-sm"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>

                        {{-- Empty State --}}
                        <div x-show="!selectedKaryawan?.transaksi || selectedKaryawan.transaksi.length === 0" 
                            class="text-center py-12">
                            <i class="fa-solid fa-receipt text-4xl text-slate-300 mb-3"></i>
                            <p class="text-slate-400">Belum ada transaksi</p>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button" @click="closeDetail()"
                        class="px-6 py-2.5 rounded-lg bg-slate-600 text-white hover:bg-slate-700 transition-colors">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL INPUT --}}
        <div x-cloak x-show="showInputModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div x-show="showInputModal" x-transition.opacity class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="closeInput()"></div>
            
            <div x-show="showInputModal" x-transition class="relative bg-white w-[500px] rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                <div class="bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] border-b border-slate-200 px-5 py-3 flex justify-between items-center">
                    <h3 class="text-base font-semibold text-[#334976]">Input Gaji Harian</h3>
                    <button @click="closeInput()" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nama Karyawan</label>
                        <input type="text" x-model="form.nama_karyawan" placeholder="Contoh: Udin Sentosa"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#344579]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#344579]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Upah Harian</label>
                        <input type="number" x-model="form.upah_harian" placeholder="0"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#344579]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Utang/Kasbon</label>
                        <input type="number" x-model="form.utang" placeholder="0"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#344579]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Keterangan (opsional)</label>
                        <input type="text" x-model="form.keterangan" placeholder="Contoh: Sakit orang tua"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-[#344579]/20">
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="closeInput()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-white">
                        Batal
                    </button>
                    <button type="button" @click="simpanData()"
                        class="px-5 py-2.5 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a]">
                        <i class="fa-solid fa-save mr-1.5"></i> Simpan
                    </button>
                </div>
            </div>
        </div>

        {{-- MODAL DELETE --}}
        <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
            <div x-show="showDeleteModal" x-transition.opacity class="absolute inset-0 bg-black/40 backdrop-blur-[2px]" @click="closeDelete()"></div>
            
            <div x-show="showDeleteModal" x-transition class="relative bg-white w-[480px] rounded-2xl shadow-lg border border-red-100 overflow-hidden">
                <div class="bg-gradient-to-r from-red-50 to-rose-50 border-b border-red-100 px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                        </div>
                        <h3 class="text-base font-semibold text-red-700">Konfirmasi Hapus</h3>
                    </div>
                    <button @click="closeDelete()" class="text-red-400 hover:text-red-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6">
                    <p class="text-slate-700">
                        Yakin hapus transaksi tanggal 
                        <span class="font-semibold" x-text="fmtTanggal(deleteItem.tanggal)"></span>?
                    </p>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3">
                    <button type="button" @click="closeDelete()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-white">
                        Batal
                    </button>
                    <button type="button" @click="doDelete()"
                        class="px-5 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        <i class="fa-solid fa-trash mr-1.5"></i> Hapus
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function gajiPage() {
            return {
                q: '',
                showInputModal: false,
                showDetailModal: false,
                showDeleteModal: false,
                deleteItem: {},
                selectedKaryawan: null,

                form: {
                    nama_karyawan: '',
                    tanggal: '',
                    upah_harian: 0,
                    utang: 0,
                    keterangan: ''
                },

                karyawan: [],

                init() {
                    this.loadData();
                    this.form.tanggal = new Date().toISOString().split('T')[0];
                },

                async loadData() {
                    try {
                        const res = await fetch('/gaji-karyawan/data', {
                            method: 'GET',
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (res.ok) {
                            const result = await res.json();
                            this.karyawan = this.groupByKaryawan(result.data || []);
                        }
                    } catch (e) {
                        console.error('Load data error:', e);
                        this.karyawan = [];
                    }
                },

                groupByKaryawan(data) {
                    const grouped = {};
                    
                    data.forEach(item => {
                        if (!grouped[item.nama_karyawan]) {
                            grouped[item.nama_karyawan] = {
                                nama: item.nama_karyawan,
                                saldo: 0,
                                transaksi: []
                            };
                        }
                        grouped[item.nama_karyawan].transaksi.push(item);
                        grouped[item.nama_karyawan].saldo = parseFloat(item.saldo);
                    });

                    const result = Object.values(grouped).map(k => {
                        k.transaksi.sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal));
                        k.transaksi = this.groupByWeek(k.transaksi);
                        return k;
                    });

                    return result;
                },

                groupByWeek(transaksi) {
                    transaksi.sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal));

                    const weeks = [];
                    let currentWeek = null;

                    transaksi.forEach(t => {
                        const date = new Date(t.tanggal);
                        const weekKey = this.getWeekKey(date);

                        if (!currentWeek || currentWeek.key !== weekKey) {
                            currentWeek = {
                                key: weekKey,
                                label: this.getWeekLabel(date),
                                items: []
                            };
                            weeks.push(currentWeek);
                        }

                        currentWeek.items.push(t);
                    });

                    return weeks;
                },

                getWeekKey(date) {
                    const d = new Date(date);
                    const day = d.getDay();
                    const diff = d.getDate() - day + (day === 0 ? -6 : 1);
                    const monday = new Date(d.setDate(diff));
                    return monday.toISOString().split('T')[0];
                },

                getWeekLabel(date) {
                    const monday = new Date(date);
                    const day = monday.getDay();
                    const diff = monday.getDate() - day + (day === 0 ? -6 : 1);
                    monday.setDate(diff);

                    const sunday = new Date(monday);
                    sunday.setDate(monday.getDate() + 6);

                    const fmt = (d) => {
                        const dd = String(d.getDate()).padStart(2, '0');
                        const mm = String(d.getMonth() + 1).padStart(2, '0');
                        return `${dd}/${mm}`;
                    };

                    return `${fmt(monday)} - ${fmt(sunday)}`;
                },

                filteredKaryawan() {
                    if (!this.q.trim()) return this.karyawan;
                    const query = this.q.toLowerCase();
                    return this.karyawan.filter(k => k.nama.toLowerCase().includes(query));
                },

                openDetail(karyawan) {
                    this.selectedKaryawan = karyawan;
                    this.showDetailModal = true;
                },

                closeDetail() {
                    this.showDetailModal = false;
                    this.selectedKaryawan = null;
                },

                weekTotal(items) {
                    return items.reduce((sum, item) => sum + (parseFloat(item.upah_harian) - parseFloat(item.utang)), 0);
                },

                calculateAverage() {
                    if (!this.selectedKaryawan?.transaksi?.length) return 0;
                    const total = this.selectedKaryawan.transaksi.reduce((sum, week) => {
                        return sum + this.weekTotal(week.items);
                    }, 0);
                    return total / this.selectedKaryawan.transaksi.length;
                },

                quickInput(nama) {
                    this.form.nama_karyawan = nama;
                    this.form.tanggal = new Date().toISOString().split('T')[0];
                    this.showInputModal = true;
                },

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
                    return `${dd}-${mm}-${yyyy}`;
                },

                closeInput() {
                    this.showInputModal = false;
                    this.form = { nama_karyawan: '', tanggal: '', upah_harian: 0, utang: 0, keterangan: '' };
                },

                async simpanData() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    
                    try {
                        const res = await fetch('/gaji-karyawan/simpan', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });

                        const result = await res.json();
                        
                        if (result.success) {
                            this.closeInput();
                            this.loadData();
                            this.showNotification('success', 'Data berhasil disimpan');
                        }
                    } catch (e) {
                        console.error(e);
                        this.showNotification('error', 'Gagal menyimpan data');
                    }
                },

                confirmDelete(item) {
                    this.deleteItem = { ...item };
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {};
                },

                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    
                    try {
                        const res = await fetch(`/gaji-karyawan/${this.deleteItem.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        });

                        const result = await res.json();
                        
                        if (result.success) {
                            this.closeDelete();
                            this.loadData();
                            this.showNotification('success', 'Data berhasil dihapus');
                        }
                    } catch (e) {
                        console.error(e);
                        this.showNotification('error', 'Gagal menghapus data');
                    }
                },

                showNotification(type, message) {
                    const bg = type === 'error' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    const icon = type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check';

                    const el = document.createElement('div');
                    el.className = `fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-md border shadow ${bg}`;
                    el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${message}</span>`;

                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3500);
                }
            };
        }
    </script>

@endsection