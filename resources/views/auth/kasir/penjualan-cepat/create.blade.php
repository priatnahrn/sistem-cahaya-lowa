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


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes drawCircle {
            to {
                stroke-dashoffset: 0;
            }
        }

        @keyframes drawCheck {
            to {
                stroke-dashoffset: 0;
            }
        }

        .success-circle {
            animation: drawCircle 0.8s ease-out forwards;
        }

        .success-check {
            animation: drawCheck 0.5s ease-out 0.8s forwards;
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
                        <span class="text-slate-700 font-medium">
                            Rp <span x-text="formatRupiah(subtotal)"></span>
                        </span>
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

        <!-- 💳 MODAL PEMBAYARAN -->
        <div x-cloak x-show="showPaymentModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showPaymentModal=false"></div>

            <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-[#344579]">Pembayaran</h3>
                    <button @click="showPaymentModal=false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                        <p class="text-sm text-slate-600"><span class="font-medium">No Faktur:</span>
                            <span class="text-slate-800" x-text="penjualanData?.no_faktur || '-'"></span>
                        </p>
                        <p class="text-sm text-slate-600"><span class="font-medium">Total Tagihan:</span>
                            <span class="text-slate-800 font-semibold"
                                x-text="formatRupiah(penjualanData?.total || 0)"></span>
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nominal Pembayaran</label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">Rp</span>
                            <input type="text" x-model="nominalBayarDisplay" @input="handleNominalInput($event)"
                                placeholder="0" inputmode="numeric"
                                class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-right
              focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579] focus:outline-none">
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                        <div class="flex gap-2">
                            <button type="button" @click="pilihMetode('cash')"
                                :class="metodePembayaran === 'cash'
                                    ?
                                    'bg-green-600 text-white border-green-600' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-money-bill-wave mr-2"></i> Tunai
                            </button>

                            <button type="button" @click="pilihMetode('transfer')"
                                :class="metodePembayaran === 'transfer'
                                    ?
                                    'bg-[#344579] text-white border-[#344579]' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-building-columns mr-2"></i> Transfer
                            </button>
                        </div>

                        <div x-show="metodePembayaran === 'transfer'" x-transition
                            class="flex gap-3 mt-3 justify-center">
                            <template x-for="bank in bankList" :key="bank.name">
                                <button type="button" @click="namaBank = bank.name"
                                    :class="namaBank === bank.name ? 'ring-2 ring-[#344579] border-[#344579]' :
                                        'hover:ring-1 hover:ring-slate-300'"
                                    class="h-14 bg-white border border-slate-300 w-full rounded-md flex items-center justify-center transition relative overflow-hidden ">
                                    <img :src="bank.logo" :alt="bank.name" class="w-1/2 object-contain">
                                    <div x-show="namaBank === bank.name" x-transition
                                        class="absolute inset-0 bg-[#344579]/10 rounded-xl"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <button @click="showPaymentModal=false"
                        class="w-[30%] px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition">
                        Batal
                    </button>
                    <button @click="simpanPembayaran()"
                        class="w-[70%] px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] shadow transition">
                        Bayar
                    </button>
                </div>
            </div>
        </div>

        <!-- ✅ MODAL PEMBAYARAN BERHASIL -->
        <div x-cloak x-show="showSuccessModal" x-transition.opacity
            class="fixed inset-0 z-[99999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeSuccessModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">

                <!-- ✅ ANIMASI SUKSES -->
                <div class="flex justify-center mb-4">
                    <svg viewBox="0 0 120 120" class="w-24 h-24">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#34D399"
                            stroke-width="10" stroke-dasharray="314" stroke-dashoffset="314" class="success-circle">
                        </circle>
                        <polyline points="40,65 55,80 85,45" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="100"
                            stroke-dashoffset="100" class="success-check"></polyline>
                    </svg>
                </div>

                <h3 class="text-2xl font-semibold text-green-700 mb-2">Pembayaran Berhasil!</h3>

                <!-- 💰 KEMBALIAN -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3 text-green-700">
                    <p class="text-sm font-medium">Kembalian:</p>
                    <p class="text-xl font-bold transition-all duration-300" x-text="formatRupiah(kembalian ?? 0)">
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a :href="printUrl" target="_blank"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Nota
                    </a>
                    <button @click="closeSuccessModal()"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Kasir
                    </button>
                </div>
            </div>
        </div>

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
                showPaymentModal: false,
                penjualanId: null,
                penjualanData: null,
                metodePembayaran: 'cash',
                namaBank: '',
                bankList: [{
                        name: 'BRI',
                        logo: '{{ asset('storage/images/bri.png') }}'
                    },
                    {
                        name: 'BNI',
                        logo: '{{ asset('storage/images/bni.png') }}'
                    },
                    {
                        name: 'Mandiri',
                        logo: '{{ asset('storage/images/mandiri.png') }}'
                    },
                ],
                nominalBayarDisplay: '',
                nominalBayar: 0,
                kembalian: 0,
                showSuccessModal: false,
                printUrl: '',





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
                            filteredSatuans: (data.gudangs || []).filter(gg => gg.gudang_id == g.gudang_id)
                                .map(gg => ({
                                    satuan_id: gg.satuan_id,
                                    nama_satuan: gg.nama_satuan
                                })),

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
                            const current = this.form.items[idx];
                            const g = current.gudangs?.[0] || {};

                            // Pastikan gudang terisi
                            if (!current.gudang_id && current.gudangs.length > 0) {
                                current.gudang_id = current.gudangs[0].gudang_id.toString();
                            }

                            // Panggil updateSatuanOptions dulu untuk isi filteredSatuans
                            this.updateSatuanOptions(idx);

                            // Pastikan satuan terisi dari filteredSatuans
                            if (!current.satuan_id && current.filteredSatuans.length > 0) {
                                current.satuan_id = current.filteredSatuans[0].satuan_id.toString();
                            }

                            // Update stok & harga
                            this.updateStockAndPrice(idx);

                            // Pastikan harga ambil dari retail
                            current.harga = this.getHargaByLevel(
                                current.gudangs.find(
                                    gg => gg.gudang_id == current.gudang_id && gg.satuan_id == current
                                    .satuan_id
                                )
                            );

                            // Update ulang form.items agar Alpine reactive
                            this.form.items = JSON.parse(JSON.stringify(this.form.items));
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



                async save() {
                    try {
                        const res = await fetch('/penjualan-cepat/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                no_faktur: this.form.no_faktur,
                                tanggal: this.form.tanggal,
                                total: this.total,
                                items: this.form.items.map(it => ({
                                    item_id: it.item_id,
                                    gudang_id: it.gudang_id,
                                    satuan_id: it.satuan_id,
                                    jumlah: it.qty,
                                    harga: it.harga,
                                    total: it.qty * it.harga,
                                }))
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.showPaymentModal = true;
                            this.penjualanId = data.id;

                            // 🧠 Tambahkan ini:
                            this.penjualanData = {
                                no_faktur: this.form.no_faktur,
                                total: this.total
                            };

                            this.notify('Transaksi disimpan. Lanjut ke pembayaran.', 'success');


                        } else {
                            this.notify(data.message || 'Gagal menyimpan transaksi', 'error');
                        }
                    } catch (err) {
                        this.notify('Terjadi kesalahan koneksi', 'error');
                    }
                },

                async savePending() {
                    try {
                        if (!this.form.items.length) {
                            this.notify('Belum ada item dalam transaksi.', 'error');
                            return;
                        }

                        const res = await fetch('/penjualan-cepat/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                no_faktur: this.form.no_faktur,
                                tanggal: this.form.tanggal,
                                total: this.total,
                                status_bayar: 'unpaid',
                                is_draft: 1, // 👈 tambahan penting
                                items: this.form.items.map(it => ({
                                    item_id: it.item_id,
                                    gudang_id: it.gudang_id,
                                    satuan_id: it.satuan_id,
                                    jumlah: it.qty,
                                    harga: it.harga,
                                    total: it.qty * it.harga,
                                }))
                            }),
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.notify('Transaksi disimpan sebagai pending.', 'success');
                            this.form.items = [];
                            this.recalc();
                        } else {
                            this.notify(data.message || 'Gagal menyimpan transaksi pending', 'error');
                        }
                    } catch (err) {
                        this.notify('Terjadi kesalahan koneksi saat menyimpan pending.', 'error');
                    }
                },



                handleNominalInput(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (!value) {
                        this.nominalBayarDisplay = '';
                        this.nominalBayar = 0;
                        this.kembalian = 0;
                        return;
                    }

                    this.nominalBayar = parseInt(value);
                    this.nominalBayarDisplay = new Intl.NumberFormat('id-ID').format(this.nominalBayar);

                    // hitung kembalian
                    if (this.penjualanData) {
                        const total = parseInt(this.penjualanData.total);
                        this.kembalian = Math.max(0, this.nominalBayar - total);
                    }
                },

                pilihMetode(metode) {
                    this.metodePembayaran = metode;
                    this.namaBank = '';
                },

                async simpanPembayaran() {
                    if (!this.penjualanId || this.nominalBayar <= 0) {
                        this.showToast('Nominal pembayaran belum diisi.', 'error');
                        return;
                    }

                    const payload = {
                        penjualan_id: this.penjualanId,
                        jumlah_bayar: this.nominalBayar,
                        sisa: 0,
                        method: this.metodePembayaran,
                        keterangan: this.metodePembayaran === 'transfer' && this.namaBank ?
                            `Transfer ke ${this.namaBank}` : this.metodePembayaran === 'cash' ?
                            'Pembayaran tunai' : this.metodePembayaran === 'qris' ?
                            'Pembayaran melalui QRIS' : this.metodePembayaran === 'wallet' ?
                            'Pembayaran melalui E-Wallet' : null,
                    };

                    try {
                        const res = await fetch(`/pembayaran`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const result = await res.json();

                        if (!result.success) throw new Error('Pembayaran gagal disimpan.');

                        this.printUrl = `/pembayaran/${result.data.id}`;
                        this.showPaymentModal = false;
                        this.showSuccessModal = true;
                    } catch (e) {
                        this.showToast(e.message || 'Gagal menyimpan pembayaran.', 'error');
                    }
                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                    // reload halaman kasir supaya form kosong lagi
                    setTimeout(() => window.location.reload(), 800);
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
