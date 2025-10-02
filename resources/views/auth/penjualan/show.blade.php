@extends('layouts.app')

@section('title', 'Edit Penjualan')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <div x-data="penjualanShowPage()" x-init="init()" class="space-y-6">

        {{-- Breadcrumb Navigasi --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                {{ $penjualan->no_faktur }}
            </span>
        </div>

        {{-- Hidden Input untuk Scanner Barcode --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        {{-- Card Utama --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">

                {{-- Info Pelanggan (readonly) --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                            value="{{ $penjualan->pelanggan->nama_pelanggan ?? 'Customer Umum' }}"
                            class="flex-1 px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />

                        @if ($penjualan->pelanggan)
                            <span class="px-3 py-1 rounded-lg text-xs font-medium"
                                :class="{
                                    'bg-blue-100 text-blue-700': '{{ $penjualan->pelanggan->level }}'
                                    === 'retail',
                                    'bg-yellow-100 text-yellow-700': '{{ $penjualan->pelanggan->level }}'
                                    === 'partai_kecil',
                                    'bg-green-100 text-green-700': '{{ $penjualan->pelanggan->level }}'
                                    === 'grosir'
                                }">
                                {{ ucwords(str_replace('_', ' ', $penjualan->pelanggan->level ?? 'retail')) }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Mode, Nomor Faktur, Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Mode --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Mode Pengambilan</label>
                        <div class="relative">
                            <select x-model="form.mode" @change="watchMode()"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-200 
                                       appearance-none pr-8 bg-white">
                                <option value="ambil">🏃 Ambil Sendiri</option>
                                <option value="antar">🚚 Antar Barang</option>
                            </select>
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- Nomor Faktur --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Faktur</label>
                        <input type="text" x-model="form.no_faktur" readonly
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi (opsional)</label>
                    <input type="text" x-model="form.deskripsi" placeholder="Catatan tambahan untuk transaksi ini..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                </div>
            </div>
        </div>

        {{-- Simulasi Scanner Manual --}}
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-6 py-4">
            <div class="flex items-start gap-3">
                <div class="text-amber-600 mt-0.5">
                    <i class="fa-solid fa-barcode text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold text-amber-900 mb-1">Simulasi Scanner Barcode</h3>
                    <p class="text-sm text-amber-700 mb-3">
                        Scanner aktif di background. Coba scan barcode atau ketik manual di kotak di bawah:
                    </p>
                    <input type="text" placeholder="Ketik/Scan barcode lalu tekan Enter (contoh: 8991102001014)"
                        @keydown.enter.prevent="handleBarcode($event)"
                        class="w-full px-4 py-2.5 border-2 border-amber-300 rounded-lg 
                              focus:border-amber-500 focus:ring-2 focus:ring-amber-200 font-mono" />
                    <p class="text-xs text-amber-600 mt-2">
                        💡 Tips: Barcode dummy ada di seeder (8991102001014, 8996001600030, dll)
                    </p>
                </div>
            </div>
        </div>

        {{-- Tabel Item --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800">Daftar Item Penjualan</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 w-40 text-center">Gudang</th>
                            <th class="px-4 py-3 w-28 text-center">Jumlah</th>
                            <th class="px-4 py-3 w-32 text-center">Satuan</th>
                            <th class="px-4 py-3 w-40 text-right">Harga</th>
                            <th class="px-4 py-3 w-40 text-right">Total</th>
                            <th class="px-2 py-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100 transition">
                                <td class="px-4 py-3 text-center font-medium" x-text="idx+1"></td>

                                {{-- Nama Item (editable dengan search) --}}
                                <td class="px-4 py-3">
                                    <div class="relative" x-data="{ open: false }">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="item.query"
                                            @input.debounce.300ms="searchItem(idx); open=true" @focus="open=true"
                                            @click.away="open=false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-8 py-2 rounded-lg border border-slate-200 text-sm" />

                                        {{-- Dropdown hasil search --}}
                                        <div x-show="open && item.query.length >= 2 && (!item.item_id || item.results.length > 0)"
                                            x-cloak
                                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <div class="p-2">
                                                <div x-show="item.results.length === 0"
                                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                    Tidak ada item ditemukan
                                                </div>
                                                <template x-for="r in item.results" :key="r.id">
                                                    <div @click="selectItem(idx, r); open=false"
                                                        class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded">
                                                        <div class="font-medium" x-text="r.nama_item"></div>
                                                        <div class="text-xs text-slate-500" x-text="r.kode_item"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                        <template x-for="g in getDistinctGudangs(item)" :key="g.gudang_id">
                                            <option :value="g.gudang_id" x-text="g.nama_gudang"></option>
                                        </template>
                                    </select>
                                    <div class="mt-1 text-xs"
                                        :class="item.stok > 0 ? 'text-slate-500' : 'text-rose-600 font-semibold'">
                                        Stok: <span x-text="formatStok(item.stok)"></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="1" x-model.number="item.jumlah" @input="recalc"
                                        class="w-20 text-center border border-slate-300 rounded-lg px-2 py-2" />
                                </td>

                                <td class="px-4 py-3">
                                    <select x-model="item.satuan_id" @change="updateStockAndPrice(idx)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                        <template x-for="s in item.filteredSatuans" :key="s.satuan_id">
                                            <option :value="s.satuan_id" x-text="s.nama_satuan"></option>
                                        </template>
                                    </select>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateHarga(idx, $event.target.value)"
                                            class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                    Rp <span x-text="formatRupiah(item.jumlah * item.harga)"></span>
                                </td>

                                {{-- Delete --}}
                                <td class="px-2 py-3 text-center">
                                    <button type="button" @click="removeItem(idx)"
                                        class="text-rose-600 hover:text-rose-800">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Button Tambah Item Manual --}}
            <div class="m-4">
                <button type="button" @click="addItemManual"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </button>
            </div>
        </div>

        {{-- Ringkasan Penjualan --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">

                {{-- Sub Total --}}
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>

                {{-- Biaya Transportasi (conditional) --}}
                <div x-show="form.mode === 'antar'" class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <div class="text-slate-600">Biaya Transportasi</div>
                    </div>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="text" :value="formatRupiah(form.biaya_transport)"
                            @input="updateTransport($event.target.value)" placeholder="0"
                            class="pl-10 pr-3 w-full border border-slate-300 rounded-lg px-3 py-2.5 text-right focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-4 mt-4"></div>

                {{-- Total Penjualan --}}
                <div class="flex justify-between items-center mb-6">
                    <div class="text-slate-700 font-bold text-lg">TOTAL PENJUALAN</div>
                    <div class="text-blue-700 text-2xl font-extrabold tracking-wide">
                        Rp <span x-text="formatRupiah(totalPembayaran)"></span>
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-3">
                    <a href="{{ route('penjualan.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                        Kembali
                    </a>
                    <button @click="update" type="button"
                        class="flex-1 px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition shadow-sm hover:shadow-md">
                        <i class="fa-solid fa-save mr-2"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $itemsJson = \App\Models\Item::with(['gudangItems.gudang', 'gudangItems.satuan'])
            ->get()
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'gudangs' => $i->gudangItems
                        ->map(
                            fn($ig) => [
                                'gudang_id' => $ig->gudang?->id,
                                'nama_gudang' => $ig->gudang?->nama_gudang,
                                'satuan_id' => $ig->satuan?->id,
                                'nama_satuan' => $ig->satuan?->nama_satuan,
                                'stok' => $ig->stok,
                                // harga langsung ambil dari relasi satuan
                                'harga_retail' => $ig->satuan?->harga_retail ?? 0,
                                'harga_partai_kecil' => $ig->satuan?->partai_kecil ?? 0,
                                'harga_grosir' => $ig->satuan?->harga_grosir ?? 0,
                            ],
                        )
                        ->toArray(),
                ],
            )
            ->toArray();

    @endphp

    <script>
        function penjualanShowPage() {
            return {
                form: {
                    id: {{ $penjualan->id }}, // <--- tambahin ini
                    pelanggan_id: {{ (int) $penjualan->pelanggan_id }},
                    mode: {{ Js::from($penjualan->mode ?? 'ambil') }},
                    no_faktur: {{ Js::from($penjualan->no_faktur) }},
                    tanggal: {{ Js::from(optional($penjualan->tanggal)->format('Y-m-d')) }},
                    deskripsi: {{ Js::from($penjualan->deskripsi) }},
                    biaya_transport: {{ (int) $penjualan->biaya_transport }},
                    items: []
                },

                subTotal: 0,
                totalPembayaran: 0,
                allItems: [],
                pelangganLevel: {{ Js::from($penjualan->pelanggan->level ?? 'retail') }},

                init() {
                    this.allItems = @json($itemsJson);

                    console.log("=== DEBUG INIT ===");
                    console.log("Penjualan ID:", {{ $penjualan->id }});
                    console.log("Items count dari server:", {{ $penjualan->items->count() }});

                    // ✅ LOAD DATA ITEMS DENGAN CARA YANG AMAN
                    this.form.items = [];

                    @foreach ($penjualan->items as $it)
                        this.form.items.push({
                            item_id: {{ $it->item_id }},
                            query: {{ Js::from($it->item->nama_item ?? '') }},
                            gudang_id: {{ $it->gudang_id }},
                            satuan_id: {{ $it->satuan_id }},
                            jumlah: {{ $it->jumlah }},
                            harga: {{ $it->harga }},
                            stok: 0,
                            gudangs: {!! json_encode(
                                $it->item->gudangItems->map(
                                        fn($ig) => [
                                            'gudang_id' => $ig->gudang_id,
                                            'nama_gudang' => $ig->gudang->nama_gudang ?? '',
                                            'satuan_id' => $ig->satuan_id,
                                            'nama_satuan' => $ig->satuan->nama_satuan ?? '',
                                            'stok' => $ig->stok ?? 0,
                                            'harga_retail' => $ig->satuan->harga_retail ?? 0,
                                            'harga_partai_kecil' => $ig->satuan->partai_kecil ?? 0,
                                            'harga_grosir' => $ig->satuan->harga_grosir ?? 0,
                                        ],
                                    )->toArray(),
                            ) !!},
                            filteredSatuans: [],
                            results: []
                        }); 
                    @endforeach


                    // Update satuan options untuk setiap item
                    this.form.items.forEach((item, idx) => {
                        this.updateSatuanOptions(idx);
                    });

                    setTimeout(() => {
                        if (this.$refs.barcodeInput) {
                            this.$refs.barcodeInput.focus();
                        }
                    }, 100);

                    this.recalc();
                },

                watchMode() {
                    if (this.form.mode === 'ambil') {
                        this.form.biaya_transport = 0;
                    }

                    // update semua harga item sesuai mode baru
                    this.form.items.forEach((item, idx) => {
                        this.updateStockAndPrice(idx);
                    });

                    this.recalc();
                },


                getHargaByLevel(g) {
                    if (!g) return 0;

                    const level = this.pelangganLevel;
                    const mode = this.form.mode;

                    if (level === 'grosir') {
                        return mode === 'ambil' ?
                            (g.harga_partai_kecil || g.harga_retail || 0) :
                            (g.harga_grosir || g.harga_retail || 0);
                    }

                    if (level === 'partai_kecil') {
                        return mode === 'ambil' ?
                            (g.harga_partai_kecil || g.harga_retail || 0) :
                            (g.harga_grosir || g.harga_retail || 0);
                    }

                    // default retail
                    return g.harga_retail || 0;
                },



                // === ITEM MANUAL ===
                addItemManual() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '',
                        gudangs: [],
                        satuan_id: '',
                        filteredSatuans: [],
                        jumlah: 1,
                        harga: 0,
                        stok: 0
                    });
                },

                searchItem(idx) {
                    const q = this.form.items[idx].query.toLowerCase();
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }
                    this.form.items[idx].results = this.allItems.filter(r =>
                        r.nama_item.toLowerCase().includes(q) || r.kode_item.toLowerCase().includes(q)
                    ).slice(0, 20);
                },

                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.gudangs = item.gudangs || [];

                    if (row.gudangs.length > 0) {
                        row.gudang_id = row.gudangs[0].gudang_id;
                        this.updateSatuanOptions(idx); // 👈 harga otomatis diisi di sini
                    }

                    this.recalc();
                },



                // === SCANNER BARCODE ===
                async handleBarcode(e) {
                    const code = e.target.value.trim();
                    if (!code) return;

                    try {
                        const res = await fetch(`/items/barcode/${encodeURIComponent(code)}`);
                        if (!res.ok) {
                            alert(`Item dengan kode "${code}" tidak ditemukan`);
                            e.target.value = '';
                            return;
                        }
                        const data = await res.json();

                        const existingIdx = this.form.items.findIndex(i => i.item_id === data.id);

                        if (existingIdx !== -1) {
                            this.form.items[existingIdx].jumlah += 1;
                        } else {
                            this.form.items.push({
                                item_id: data.id,
                                query: data.nama_item,
                                gudang_id: data.gudangs.length ? data.gudangs[0].gudang_id : '',
                                gudangs: data.gudangs,
                                satuan_id: '',
                                filteredSatuans: [],
                                jumlah: 1,
                                harga: 0, // 👈 nanti diisi oleh updateStockAndPrice
                                stok: 0,
                                results: []
                            });

                            const idx = this.form.items.length - 1;
                            this.updateSatuanOptions(idx);

                        }

                        this.recalc();
                        e.target.value = '';

                        setTimeout(() => {
                            if (this.$refs.barcodeInput) {
                                this.$refs.barcodeInput.focus();
                            }
                        }, 50);

                    } catch (err) {
                        console.error("Error handleBarcode:", err);
                        alert('Terjadi kesalahan saat memproses barcode');
                        e.target.value = '';
                    }
                },

                // === GUDANG & SATUAN ===
                getDistinctGudangs(item) {
                    if (!item.gudangs || item.gudangs.length === 0) return [];
                    const seen = new Set();
                    return item.gudangs.filter(g => {
                        if (seen.has(g.gudang_id)) return false;
                        seen.add(g.gudang_id);
                        return true;
                    });
                },

                updateSatuanOptions(idx) {
                    const item = this.form.items[idx];
                    if (!item.gudangs || item.gudangs.length === 0) {
                        item.filteredSatuans = [];
                        return;
                    }

                    item.filteredSatuans = item.gudangs.filter(g => g.gudang_id == item.gudang_id);

                    if (item.filteredSatuans.length > 0) {
                        if (!item.satuan_id) {
                            item.satuan_id = item.filteredSatuans[0].satuan_id;
                        }
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        item.harga = 0;
                    }
                },

                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(
                        g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id
                    );

                    if (selected) {
                        item.stok = selected.stok || 0;
                        item.harga = this.getHargaByLevel(selected); // 👈 langsung ambil sesuai level
                    } else {
                        item.stok = 0;
                        item.harga = 0;
                    }

                    this.recalc();
                },



                updateHarga(idx, val) {
                    let num = val.replace(/[^0-9]/g, '');
                    this.form.items[idx].harga = parseInt(num) || 0;
                    this.recalc();
                },

                updateTransport(val) {
                    let num = val.replace(/[^0-9]/g, '');
                    this.form.biaya_transport = parseInt(num) || 0;
                    this.recalc();
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                recalc() {
                    this.subTotal = this.form.items.reduce((sum, i) => sum + (i.jumlah * i.harga), 0);
                    const transport = this.form.mode === 'antar' ? (this.form.biaya_transport || 0) : 0;
                    this.totalPembayaran = this.subTotal + transport;
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID').format(n || 0);
                },

                formatStok(val) {
                    if (val == null || val === '') return '0';
                    const num = parseFloat(val);
                    if (Number.isInteger(num)) {
                        return num.toString();
                    }
                    return num.toLocaleString('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).replace('.', ',');
                },

                async update() {
                    if (this.form.items.length === 0) {
                        alert('Minimal harus ada 1 item');
                        return;
                    }

                    // Validasi stok
                    for (let i = 0; i < this.form.items.length; i++) {
                        const item = this.form.items[i];
                        if (!item.item_id || !item.gudang_id || !item.satuan_id) {
                            alert('Mohon lengkapi semua data item');
                            return;
                        }
                        if (item.jumlah > item.stok) {
                            alert(`Stok tidak cukup untuk: ${item.query}\nStok: ${this.formatStok(item.stok)}`);
                            return;
                        }
                    }

                    const payload = {
                        ...this.form,
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        items: this.form.items.map(i => ({
                            item_id: i.item_id,
                            gudang_id: i.gudang_id,
                            satuan_id: i.satuan_id,
                            jumlah: parseFloat(i.jumlah),
                            harga: parseFloat(i.harga),
                            total: parseFloat(i.jumlah) * parseFloat(i.harga)
                        }))
                    };

                    try {
                        const res = await fetch(`/penjualan/${this.form.id}/update`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });


                        if (res.ok) {
                            alert('Perubahan berhasil disimpan!');
                            window.location.href = "{{ route('penjualan.index') }}";
                        } else {
                            const errorData = await res.json();
                            alert('Gagal update: ' + (errorData.message || 'Unknown error'));
                        }
                    } catch (err) {
                        console.error('Error update:', err);
                        alert('Terjadi kesalahan saat menyimpan perubahan');
                    }
                }
            }
        }
    </script>
@endsection
