<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Penjualan Cepat - Kasir</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        /* Hapus spinner number */
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .no-spinner {
            -moz-appearance: textfield;
        }
    </style>
</head>

<body class="bg-slate-100 text-slate-700 min-h-screen flex flex-col">

    <div x-data="penjualanCepatFull()" x-init="init()" class="min-h-screen bg-slate-100">
        <!-- HEADER -->
        <header class="bg-[#344579] text-white py-4 px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button @click="back()"
                    class="bg-[#2c3e6b] hover:bg-[#24355b] px-3 py-2 rounded-md flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </button>
                <h1 class="font-semibold text-lg">Penjualan Cepat</h1>
            </div>

            <div class="text-right text-sm leading-tight">
                <div>No. Faktur: <span class="font-bold" x-text="form.no_faktur"></span></div>
                <div x-text="fmtDate(form.tanggal)"></div>
            </div>
        </header>

        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">


        <!-- MAIN -->
        <main class="max-w-[95%] mx-auto mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
            <!-- LEFT: Items (2/3) -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl  p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-700">Daftar Item</h2>


                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-slate-600">
                                <th class="px-3 py-3 w-10 text-center">No</th>
                                <th class="px-3 py-3">Nama Item</th>
                                <th class="px-3 py-3 text-center w-40">Gudang</th>
                                <th class="px-3 py-3 text-center w-32">Satuan</th>
                                <th class="px-3 py-3 text-center w-20">Jumlah</th>
                                <th class="px-3 py-3 text-center w-32">Harga</th>
                                <th class="px-3 py-3 text-center w-32">Total</th>
                                <th class="px-3 py-3 w-10"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(it, idx) in form.items" :key="idx">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 text-slate-700">
                                    <td class="px-3 py-2 text-center font-medium" x-text="idx + 1"></td>

                                    <!-- Item search -->
                                    <td class="px-3 py-2">
                                        <div class="relative" x-data="{ open: false }" @click.away="open=false">
                                            <input type="text" x-model="it.query"
                                                @input.debounce.300ms="searchItem(idx)"
                                                @focus="it.query.length>=2 && searchItem(idx); open = true"
                                                placeholder="Cari Item..."
                                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:border-[#344579] focus:ring-2 focus:ring-[#344579]/20 text-sm">
                                            <i
                                                class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                            <!-- dropdown results -->
                                            <div x-show="it.results && it.results.length > 0 && open" x-transition
                                                class="absolute z-30 bg-white border border-slate-200 rounded-lg  w-full mt-1 max-h-56 overflow-auto text-sm">
                                                <template x-for="r in it.results" :key="r.id">
                                                    <div @click="selectItem(idx, r); open=false"
                                                        class="px-3 py-2 hover:bg-blue-50 cursor-pointer">
                                                        <div class="font-medium text-slate-800" x-text="r.nama_item">
                                                        </div>
                                                        <div class="text-xs text-slate-500" x-text="r.kode_item"></div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Gudang -->
                                    <td class="px-3 py-2 text-center">
                                        <div class="relative">
                                            <select x-model="it.gudang_id" @change="updateSatuanOptions(idx)"
                                                class="border border-slate-300 rounded-lg h-[42px] px-3 pr-10 w-full bg-white appearance-none">
                                                <option value="">Pilih</option>
                                                <template x-for="g in getDistinctGudangs(it)" :key="g.gudang_id">
                                                    <option :value="g.gudang_id.toString()" x-text="g.nama_gudang">
                                                    </option>
                                                </template>
                                            </select>

                                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1" x-show="it.gudang_id">
                                            Stok: <span x-text="formatStok(getStockForSelected(it))"></span>
                                        </div>
                                    </td>

                                    <!-- Satuan -->
                                    <td class="px-3 py-2 text-center">
                                        <div class="relative">
                                            <select x-model="it.satuan_id" @change="updateHarga(idx)"
                                                class="border border-slate-300 rounded-lg h-[42px] px-3 pr-10 w-full bg-white appearance-none">
                                                <option value="">Pilih</option>
                                                <template x-for="s in it.filteredSatuans" :key="s.satuan_id">
                                                    <option :value="s.satuan_id.toString()" x-text="s.nama_satuan">
                                                    </option>
                                                </template>
                                            </select>

                                            <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                                fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </td>


                                    <!-- Qty -->
                                    <td class="px-3 py-2 text-center">
                                        <input type="number" min="1" x-model.number="it.qty"
                                            @input="recalc()"
                                            class="no-spinner w-16 text-center border border-slate-300 rounded-lg py-2">
                                    </td>

                                    <!-- Harga -->
                                    <td class="px-3 py-2 text-right">
                                        <div class="relative">
                                            <span
                                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                            <input type="text" :value="formatRupiah(it.harga)"
                                                @input="
                            const clean = $event.target.value.replace(/\D/g, '');
                            it.harga = parseInt(clean || 0);
                            it.manual = true;
                            recalc();
                        "
                                                class="no-spinner pl-8 pr-2 w-full text-right border border-slate-300 rounded-lg py-2">
                                        </div>
                                    </td>

                                    <!-- Total -->
                                    <td class="px-3 py-2 text-right font-semibold">
                                        Rp <span x-text="formatRupiah((it.qty||0) * (it.harga||0))"></span>
                                    </td>

                                    <!-- Delete -->
                                    <td class="px-3 py-2 text-center">
                                        <button @click="removeItem(idx)" class="text-rose-600 hover:text-rose-800">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>

                    </table>
                </div>

                <!-- add item button -->
                <div class="mt-4">
                    <button @click="addItem()"
                        class="w-full border-2 border-dashed border-slate-300 rounded-lg py-3 text-slate-600 hover:bg-slate-50 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus"></i> Tambah Item
                    </button>
                </div>
            </div>

            <!-- RIGHT: Ringkasan -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 h-fit">
                <h3 class="font-semibold text-slate-700 mb-4">Ringkasan Penjualan</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Subtotal</span>
                        <span class="text-slate-700 font-medium">Rp <span
                                x-text="formatRupiah(subtotal)"></span></span>
                    </div>

                    <div class="flex justify-between items-center">
                        <span class="text-slate-600">Uang Diterima</span>
                        <div class="relative w-40">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                            <input type="number" x-model.number="uangDiterima" @input="updateKembalian()"
                                class="no-spinner pl-8 pr-2 w-full text-right border border-slate-300 rounded-lg py-2">
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-slate-700 font-semibold">Kembalian</span>
                        <span class="text-green-600 font-bold">Rp <span
                                x-text="formatRupiah(kembalian)"></span></span>
                    </div>

                    <div class="border-t border-slate-200 my-2"></div>

                    <div class="flex justify-between text-base font-semibold text-slate-800">
                        <span>Total</span>
                        <span>Rp <span x-text="formatRupiah(total)"></span></span>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button @click="savePending()" type="button"
                        class="w-full bg-white hover:bg-yellow-600 text-yellow-600 hover:text-white border border-yellow-600 py-2.5 rounded-md text-sm font-medium">
                        Pending
                    </button>

                    <button @click="save()" type="button"
                        class="w-full bg-[#344579] hover:bg-[#2d3f6b] text-white py-2.5 rounded-md text-sm font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
                    </button>
                </div>
            </div>
        </main>
    </div>

    <script>
        function penjualanCepatFull() {
            return {
                form: {
                    no_faktur: '',
                    tanggal: '',
                    items: []
                },

                allItems: [],
                selectedPelangganLevel: 'retail',
                subtotal: 0,
                total: 0,
                uangDiterima: 0,
                kembalian: 0,


                init() {
                    this.form.no_faktur = @json($noFaktur ?? '');
                    this.form.tanggal = @json(now()->format('Y-m-d'));
                    this.allItems = @json($itemsJson ?? []);


                    this.focusScanner();
                    document.addEventListener('click', (e) => {
                        const tag = e.target.tagName?.toLowerCase();
                        if (!['input', 'textarea', 'select'].includes(tag)) this.focusScanner();
                    });
                },
                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                },



                recalc() {
                    this.subtotal = this.form.items.reduce(
                        (sum, it) => sum + ((+it.jumlah || +it.qty || 0) * (+it.harga || 0)),
                        0
                    );
                    this.total = this.subtotal;
                    this.updateKembalian();
                },

                updateKembalian() {
                    this.kembalian = Math.max(0, (this.uangDiterima || 0) - (this.total || 0));
                },
                // === TAMBAH ITEM MANUAL ===
                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        keterangan: '',
                        results: [],
                        gudang_id: '',
                        gudangs: [],
                        satuan_id: '',
                        filteredSatuans: [],
                        qty: 1, // 👈 ganti jumlah ke qty
                        harga: 0,
                        stok: 0,
                        harga_manual: false
                    });



                },

                removeItem(i) {
                    this.form.items.splice(i, 1);
                },

                // === SEARCH ITEM (SAMA PERSIS DENGAN PENJUALAN BIASA) ===
                searchItem(idx) {
                    const q = this.form.items[idx].query.toLowerCase();
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }

                    this.form.items[idx].results = this.allItems
                        .filter(r =>
                            (r.nama_item && r.nama_item.toLowerCase().includes(q)) ||
                            (r.kode_item && r.kode_item.toLowerCase().includes(q))
                        )
                        .slice(0, 20);
                },

                // === PILIH ITEM DARI DROPDOWN ===
                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.gudangs = item.gudangs || [];
                    row.harga_manual = false;

                    if (row.gudangs.length > 0) {
                        row.gudang_id = row.gudangs[0].gudang_id;
                        this.updateSatuanOptions(idx);
                    } else {
                        row.gudang_id = '';
                        row.satuan_id = '';
                        row.filteredSatuans = [];
                        row.stok = 0;
                        row.harga = 0;
                    }
                },

                // === FILTER GUDANG TANPA DUPLIKAT ===
                getDistinctGudangs(item) {
                    if (!item.gudangs || item.gudangs.length === 0) return [];
                    const seen = new Set();
                    return item.gudangs.filter(g => {
                        if (seen.has(g.gudang_id)) return false;
                        seen.add(g.gudang_id);
                        return true;
                    });
                },

                // === UPDATE DROPDOWN SATUAN ===
                updateSatuanOptions(idx) {
                    const item = this.form.items[idx];
                    if (!item.gudangs || item.gudangs.length === 0) {
                        item.filteredSatuans = [];
                        return;
                    }

                    // filter satuan berdasarkan gudang
                    item.filteredSatuans = item.gudangs.filter(g => g.gudang_id == item.gudang_id);

                    if (item.filteredSatuans.length > 0) {
                        // ambil default satuan kalau belum ada
                        if (!item.satuan_id) {
                            item.satuan_id = item.filteredSatuans[0].satuan_id;
                        }

                        // selalu update stok & harga walau satuan sudah terisi
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        item.harga = 0;
                    }
                },

                // === HARGA BERDASARKAN LEVEL ===
                getHargaByLevel(g) {
                    if (!g) return 0;
                    const level = (this.selectedPelangganLevel || 'retail').toLowerCase();

                    if (level === 'grosir') return parseFloat(g.harga_grosir || g.harga_retail || 0);
                    if (level === 'partai_kecil') return parseFloat(g.partai_kecil || g.harga_retail || 0);

                    // default ke harga retail
                    return parseFloat(g.harga_retail || 0);
                },


                // === UPDATE STOK & HARGA ===
                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(
                        g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id
                    );

                    if (selected) {
                        item.stok = selected.stok || 0;
                        item.harga = this.getHargaByLevel(selected);
                    } else {
                        item.stok = 0;
                        item.harga = 0;
                    }
                },

                getStockForSelected(it) {
                    if (!it.gudang_id || !it.satuan_id) return 0;
                    const found = it.gudangs.find(
                        g => g.gudang_id == it.gudang_id && g.satuan_id == it.satuan_id
                    );
                    return found ? found.stok || 0 : 0;
                },


                // === UTIL ===
                formatRupiah(val) {
                    const num = parseFloat(val) || 0;
                    return new Intl.NumberFormat('id-ID').format(num);
                },

                formatStok(val) {
                    if (val == null || val === '') return '0';
                    const num = parseFloat(val);
                    return Number.isInteger(num) ?
                        num.toString() :
                        num.toLocaleString('id-ID', {
                            maximumFractionDigits: 2
                        }).replace('.', ',');
                },

                fmtDate(v) {
                    if (!v) return '-';
                    const d = new Date(v);
                    return d.toLocaleDateString('id-ID', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                },

                async handleBarcode(e) {
                    const code = e.target.value.trim();
                    if (!code) return;

                    try {
                        const res = await fetch(`/items/barcode/${encodeURIComponent(code)}`);
                        if (!res.ok) {
                            (this.notify || (() => {}))(`Item dengan kode "${code}" tidak ditemukan`, 'error');
                            e.target.value = '';
                            return;
                        }

                        const data = await res.json();
                        console.log('DATA SCAN:', data);

                        // jika item sudah ada, cukup tambah qty
                        const existingIdx = this.form.items.findIndex(i => i.item_id === data.id);
                        if (existingIdx !== -1) {
                            this.form.items[existingIdx].qty += 1;
                            this.recalc();
                            e.target.value = '';
                            setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                            return;
                        }

                        // ambil gudang pertama (default)
                        const g = data.gudangs?.[0] || {};

                        // buat item baru
                        const newItem = {
                            item_id: data.id,
                            query: data.nama_item,
                            keterangan: '',
                            gudangs: data.gudangs || [],
                            gudang_id: g.gudang_id ? g.gudang_id.toString() : '',
                            satuan_id: g.satuan_id ? g.satuan_id.toString() : '',
                            filteredSatuans: (data.gudangs || []).filter(gg => gg.gudang_id == g.gudang_id),
                            qty: 1,
                            stok: parseFloat(g.stok || 0),
                            harga: parseFloat(g.harga_retail || 0), // default retail
                            harga_manual: false,
                            results: []
                        };

                        // push item baru
                        this.form.items.push(newItem);

                        // trigger reactivity
                        this.form.items = JSON.parse(JSON.stringify(this.form.items));

                        // pastikan gudang & satuan terisi default
                        this.$nextTick(() => {
                            const idx = this.form.items.length - 1;

                            // jika belum ada gudang, ambil default pertama
                            if (!this.form.items[idx].gudang_id && this.form.items[idx].gudangs.length > 0) {
                                this.form.items[idx].gudang_id = this.form.items[idx].gudangs[0].gudang_id
                                    .toString();
                            }

                            // update daftar satuan berdasarkan gudang terpilih
                            this.updateSatuanOptions(idx);

                            // jika belum ada satuan, ambil default pertama dari filteredSatuans
                            if (!this.form.items[idx].satuan_id && this.form.items[idx].filteredSatuans.length >
                                0) {
                                this.form.items[idx].satuan_id = this.form.items[idx].filteredSatuans[0]
                                    .satuan_id.toString();
                            }

                            // update stok & harga sesuai level (retail)
                            this.updateStockAndPrice(idx);
                            this.recalc();
                        });

                        (this.notify || (() => {}))(`${data.nama_item} ditambahkan`, 'success');
                    } catch (err) {
                        console.error("Error handleBarcode:", err);
                        (this.notify || (() => {}))('Terjadi kesalahan saat memproses barcode', 'error');
                    } finally {
                        e.target.value = '';
                        setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                    }
                },


                notify(message, type = 'info') {
                    console.log(`[${type.toUpperCase()}] ${message}`);

                    const bg =
                        type === 'success' ? 'bg-green-500' :
                        type === 'error' ? 'bg-red-500' :
                        'bg-blue-500';

                    const el = document.createElement('div');
                    el.className = `${bg} text-white px-4 py-2 rounded-lg fixed top-5 right-5 shadow-lg z-50 transition`;
                    el.textContent = message;
                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 2500);
                },


            };
        }
    </script>


</body>

</html>
