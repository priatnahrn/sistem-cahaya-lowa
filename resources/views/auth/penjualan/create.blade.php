@extends('layouts.app')

@section('title', 'Tambah Penjualan Baru')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Alpine cloak fix (agar elemen dengan x-cloak hidden sebelum Alpine jalan) --}}
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Root Alpine Component --}}
    <div x-data="penjualanCreatePage()" x-init="init();
    $watch('form.mode', () => watchMode())" data-no-faktur="{{ $noFakturPreview }}"
        data-tanggal="{{ now()->toDateString() }}" class="space-y-6">

        {{-- Breadcrumb Navigasi --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                Tambah Penjualan Baru
            </span>
        </div>

        {{-- Hidden Input untuk Scanner Barcode --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">


        {{-- Card Utama --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">

                {{-- Input Pelanggan dengan Search + Dropdown --}}
                <div @click.away="openResults = false">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <div class="relative">
                        {{-- Icon Search --}}
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>

                        {{-- Input Search Pelanggan --}}
                        <input type="text" x-model="pelangganQuery"
                            @input.debounce.300ms="
                                if (pelangganQuery.length >= 2) {
                                    searchPelanggan(); 
                                    openResults = true;
                                } else {
                                    // reset state pelanggan jika input kosong
                                    form.pelanggan_id = null;
                                    selectedPelangganLevel = null;
                                    selectedPelangganNames = '';
                                    form.is_walkin = false;
                                    pelangganResults = [];
                                    openResults = false;
                                    updateAllItemPrices();
                                }
                            "
                            @blur="if (!form.pelanggan_id && pelangganQuery && pelangganResults.length === 0) {
                                selectedPelangganNames = 'Customer';
                                selectedPelangganLevel = null;
                                form.pelanggan_id = null;
                                form.is_walkin = true;
                            }"
                            @focus="openResults = (pelangganQuery.length >= 2)"
                            placeholder="Cari pelanggan (ketik minimal 2 huruf) atau biarkan kosong untuk umum"
                            class="w-full pl-12 pr-10 py-2.5 rounded-lg border border-slate-300 
                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                        {{-- Dropdown Hasil Pencarian --}}
                        <div x-show="openResults && pelangganQuery.length >= 2" x-cloak
                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 
                                    rounded-lg shadow-lg text-sm max-h-56 overflow-auto">

                            {{-- Loading State --}}
                            <template x-if="pelangganLoading">
                                <div class="px-4 py-3 text-gray-500 text-center">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Mencari pelanggan...
                                </div>
                            </template>

                            {{-- Jika hasil ada --}}
                            <template x-if="!pelangganLoading && pelangganResults.length > 0">
                                <ul>
                                    <template x-for="p in pelangganResults" :key="p.id">
                                        <li @click="selectPelanggan(p); openResults = false"
                                            class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition border-b last:border-b-0">
                                            <div class="font-medium text-slate-800" x-text="p.nama_pelanggan"></div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <small class="text-slate-500" x-text="p.kontak || '-'"></small>
                                                <span class="px-2 py-0.5 rounded text-xs bg-slate-100"
                                                    x-text="formatLevel(p.level) || '-'"></span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            {{-- Jika hasil kosong --}}
                            <template x-if="!pelangganLoading && pelangganResults.length === 0">
                                <div class="px-4 py-3">
                                    <div class="text-gray-500 italic mb-3 text-center">
                                        <i class="fa-solid fa-user-slash mr-1"></i>
                                        Pelanggan "<span x-text="pelangganQuery"></span>" tidak ditemukan
                                    </div>
                                    {{-- Tombol Tambah Pelanggan Baru (buka modal) --}}
                                    <button type="button" @click="openTambahPelanggan(); openResults = false"
                                        class="w-full px-4 py-2 bg-[#334976] hover:bg-[#2d3d6d] text-white rounded-lg transition font-medium">
                                        <i class="fa-solid fa-user-plus mr-2"></i> Tambah Pelanggan Baru
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Badge info pelanggan yang dipilih --}}
                    <div x-show="form.pelanggan_id || selectedPelangganNames === 'Customer'"
                        class="mt-2 flex items-center gap-2">
                        <i class="fa-solid fa-check-circle text-green-600"></i>
                        <span class="font-normal text-green-600 text-sm"
                            x-text="selectedPelangganNames || 'Customer'"></span>
                        <span class="ml-1 text-xs px-2 py-0.5 rounded font-medium"
                            :class="{
                                'bg-blue-100 text-blue-700': selectedPelangganLevel === 'retail',
                                'bg-yellow-100 text-yellow-700': selectedPelangganLevel === 'partai_kecil',
                                'bg-green-100 text-green-700': selectedPelangganLevel === 'grosir'
                            }"
                            x-text="selectedPelangganLevel ? formatLevel(selectedPelangganLevel) : '-'">
                        </span>
                    </div>
                </div>

                {{-- Mode Pengambilan, Nomor Faktur, Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Mode --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Mode Pengambilan</label>
                        <div class="relative">
                            <select name="mode" x-model="form.mode"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-200 
                                           appearance-none pr-8 bg-white ">
                                <option value="ambil">🏃 Ambil Sendiri</option>
                                <option value="antar">🚚 Antar Barang</option>
                            </select>

                            {{-- Custom arrow --}}
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
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600">
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi (opsional)</label>
                    <input type="text" x-model="form.deskripsi" placeholder="Catatan tambahan untuk transaksi ini..."
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>
        </div>

        {{-- 🔎 Simulasi Scanner Manual --}}
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

        {{-- === TABEL ITEM === --}}
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
                                {{-- # --}}
                                <td class="px-4 py-3 text-center font-medium" x-text="idx+1"></td>

                                {{-- Nama Item (manual search) --}}
                                <td class="px-4 py-3">
                                    <div class="relative" x-data="{ open: false }">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="item.query"
                                            @input.debounce.300ms="searchItem(idx); open=true" @focus="open=true"
                                            @click.away="open=false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-8 py-2 rounded-lg border border-slate-200 text-sm" />

                                        {{-- Dropdown hasil search --}}
                                        <div x-show="open && item.query.length >= 2 && !item.item_id" x-cloak
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

                                {{-- Gudang --}}
                                <td class="px-4 py-3">
                                    <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                        <template x-for="g in getDistinctGudangs(item)" :key="g.gudang_id">
                                            <option :value="g.gudang_id" x-text="g.nama_gudang"></option>
                                        </template>
                                    </select>
                                    {{-- Info stok --}}
                                    <div class="mt-1 text-xs"
                                        :class="item.stok > 0 ? 'text-slate-500' : 'text-rose-600 font-semibold'">
                                        Stok tersedia: <span x-text="formatStok(item.stok)"></span>
                                    </div>
                                </td>

                                {{-- Jumlah --}}
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="1" x-model.number="item.jumlah" @input="recalc"
                                        class="w-20 text-center border border-slate-300 rounded-lg px-2 py-2" />
                                </td>

                                {{-- Satuan --}}
                                <td class="px-4 py-3">
                                    <select x-model="item.satuan_id" @change="updateStockAndPrice(idx)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2">
                                        <template x-for="s in item.filteredSatuans" :key="s.satuan_id">
                                            <option :value="s.satuan_id" x-text="s.nama_satuan"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- Harga --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateManualPrice(idx, $event.target.value)"
                                            class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                    </div>
                                </td>


                                {{-- Total --}}
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
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </button>
            </div>
        </div>


        {{-- === RINGKASAN PENJUALAN === --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">
                {{-- Sub Total --}}
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>

                {{-- Biaya Transportasi (hanya jika antar) --}}
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

                {{-- TOTAL PENJUALAN --}}
                <div class="flex justify-between items-center mb-6">
                    <div class="text-slate-700 font-bold text-lg">TOTAL PENJUALAN</div>
                    <div class="text-blue-700 text-2xl font-extrabold tracking-wide">
                        Rp <span x-text="formatRupiah(totalPembayaran)"></span>
                    </div>
                </div>

                {{-- Info Status --}}
                <div x-show="form.items.length === 0"
                    class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                    <i class="fa-solid fa-info-circle mr-1"></i> Belum ada item yang ditambahkan
                </div>

                {{-- Tombol Aksi --}}
                <div class="flex gap-3">
                    <button @click="saveDraft" type="button"
                        class="px-5 py-2.5 rounded-lg border border-yellow-500 text-yellow-500 hover:bg-yellow-500 transition">
                        Pending
                    </button>

                    <button @click="save" type="button" :disabled="!isValid()"
                        class="flex-1 px-5 py-2.5 rounded-lg text-white font-medium transition"
                        :class="isValid() ?
                            'bg-[#334976] hover:bg-[#2d3f6d] cursor-pointer shadow-sm hover:shadow-md' :
                            'bg-gray-300 cursor-not-allowed opacity-60'">
                        Simpan
                    </button>
                </div>

            </div>
        </div>


        {{-- Modal Tambah Pelanggan Baru --}}
        <div x-show="showModalTambahPelanggan" x-cloak
            class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50">

            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold mb-4">Tambah Pelanggan Baru</h2>

                {{-- Form isi data pelanggan --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Pelanggan</label>
                        <input type="text" x-model="newPelanggan.nama_pelanggan"
                            class="w-full px-3 py-2 border rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                        <input type="text" x-model="newPelanggan.kontak"
                            class="w-full px-3 py-2 border rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Alamat</label>
                        <textarea x-model="newPelanggan.alamat" class="w-full px-3 py-2 border rounded-lg border-slate-300"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Level</label>
                        <select x-model="newPelanggan.level" class="w-full px-3 py-2 border rounded-lg border-slate-300">
                            <option value="retail">Retail</option>
                            <option value="partai_kecil">Partai Kecil</option>
                            <option value="grosir">Grosir</option>
                        </select>
                    </div>
                </div>

                {{-- Tombol Aksi Modal --}}
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showModalTambahPelanggan=false"
                        class="px-4 py-2 rounded-lg border border-slate-300">Batal</button>
                    <button type="button" @click="savePelangganBaru"
                        class="px-4 py-2 rounded-lg bg-blue-600 text-white">Simpan</button>
                </div>
            </div>
        </div>


        <div x-show="showPrintModal" x-cloak class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold mb-4">Penjualan Berhasil Disimpan</h2>
                <p class="text-slate-600 mb-6">Pilih opsi berikut:</p>

                <div class="flex flex-col gap-3">
                    <a :href="`/penjualan/${savedPenjualanId}/print?type=kecil`" target="_blank"
                        class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-center">
                        <i class="fa-solid fa-receipt mr-2"></i> Print Nota Kecil
                    </a>
                    <a :href="`/penjualan/${savedPenjualanId}/print?type=besar`" target="_blank"
                        class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-center">
                        <i class="fa-solid fa-file-invoice mr-2"></i> Print Nota Besar
                    </a>
                    <button @click="window.location.href='/penjualan'"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600">
                        Simpan Saja
                    </button>
                </div>
            </div>
        </div>




    </div>

    @php
        $itemsJson = $items
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
                                // 👇 ambil harga langsung dari relasi satuan, sama kayak di update
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


    {{-- Alpine Component --}}
    <script>
        function penjualanCreatePage() {
            return {
                // === STATE ===
                pelangganQuery: '',
                pelangganResults: [],
                pelangganLoading: false,
                openResults: false,

                form: {
                    pelanggan_id: null,
                    mode: 'ambil',
                    no_faktur: '',
                    tanggal: '',
                    deskripsi: '',
                    biaya_transport: 0,
                    is_walkin: false,
                    items: []
                },

                selectedPelangganLevel: null,
                selectedPelangganNames: '',

                showModalTambahPelanggan: false,
                newPelanggan: {
                    nama_pelanggan: '',
                    kontak: '',
                    alamat: '',
                    level: 'retail'
                },

                subTotal: 0,
                totalPembayaran: 0,

                allItems: [],

                savedPenjualanId: null,
                showPrintModal: false,


                // === INIT ===
                init() {
                    this.form.no_faktur = this.$el.getAttribute('data-no-faktur') || '';
                    this.form.tanggal = this.$el.getAttribute('data-tanggal') || '';
                    this.subTotal = 0;
                    this.totalPembayaran = 0;
                    this.form.items = [];

                    this.allItems = @json($itemsJson ?? []);

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
                    this.updateAllItemPrices();
                    this.recalc();
                },

                // === Helper Harga ===
                getPriceFromSelected(selected, level, is_walkin, mode) {
                    if (!selected) return 0;

                    // customer umum → retail
                    if (!level || is_walkin) return Number(selected.harga_retail || 0);

                    level = level.toLowerCase();

                    if (level === 'grosir') {
                        return mode === 'ambil' ?
                            (Number(selected.harga_partai_kecil) || Number(selected.harga_retail) || 0) :
                            (Number(selected.harga_grosir) || Number(selected.harga_retail) || 0);
                    }

                    if (level === 'partai_kecil') {
                        return mode === 'ambil' ?
                            (Number(selected.harga_partai_kecil) || Number(selected.harga_retail) || 0) :
                            (Number(selected.harga_grosir) || Number(selected.harga_retail) || 0);
                    }

                    // default retail
                    return Number(selected.harga_retail || 0);
                },



                // === Manual Add ===
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
                        stok: 0,
                        harga_manual: false
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
                    row.harga_manual = false;

                    if (row.gudangs.length > 0) {
                        row.gudang_id = row.gudangs[0].gudang_id;
                        this.updateSatuanOptions(idx);
                    } else {
                        console.warn('Item tidak memiliki gudang:', item);
                    }

                    this.recalc();
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
                        item.satuan_id = item.filteredSatuans[0].satuan_id;
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        if (!item.harga_manual) item.harga = 0;
                    }
                },

                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(
                        g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id
                    );

                    if (selected) {
                        item.stok = selected.stok || 0;
                        const level = (this.selectedPelangganLevel || '').toString().toLowerCase();
                        const computedPrice = this.getPriceFromSelected(
                            selected,
                            level,
                            this.form.is_walkin,
                            this.form.mode
                        );

                        if (!item.harga_manual) {
                            item.harga = computedPrice;
                        }
                    } else {
                        item.stok = 0;
                        if (!item.harga_manual) item.harga = 0;
                    }

                    this.recalc();
                },

                updateAllItemPrices() {
                    this.form.items.forEach((item, idx) => {
                        this.updateStockAndPrice(idx);
                        if (!item.harga_manual && item.satuan_id) {
                            this.fetchPriceForItem(idx);
                        }
                    });
                },

                // === PELANGGAN ===
                async searchPelanggan() {
                    if (this.pelangganQuery.length < 2) {
                        this.pelangganResults = [];
                        return;
                    }

                    this.pelangganLoading = true;
                    try {
                        const res = await fetch(`/pelanggan/search?q=${encodeURIComponent(this.pelangganQuery)}`);
                        const data = await res.json();
                        this.pelangganResults = data;
                    } catch (err) {
                        console.error("Error search pelanggan:", err);
                        this.pelangganResults = [];
                    } finally {
                        this.pelangganLoading = false;
                    }
                },

                selectPelanggan(p) {
                    this.form.pelanggan_id = p.id;
                    this.selectedPelangganNames = p.nama_pelanggan;
                    this.selectedPelangganLevel = (p.level || 'retail').toString().toLowerCase();
                    this.form.is_walkin = false;
                    this.pelangganQuery = p.nama_pelanggan;
                    this.openResults = false;
                    this.updateAllItemPrices();
                },

                formatLevel(level) {
                    if (!level) return '';
                    return level.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                },

                openTambahPelanggan() {
                    this.showModalTambahPelanggan = true;
                    this.newPelanggan = {
                        nama_pelanggan: '',
                        kontak: '',
                        alamat: '',
                        level: 'retail'
                    };
                },

                async savePelangganBaru() {
                    if (!this.newPelanggan.nama_pelanggan) {
                        alert('Nama pelanggan wajib diisi');
                        return;
                    }

                    try {
                        const res = await fetch('/pelanggan/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.newPelanggan)
                        });

                        if (!res.ok) throw new Error('Gagal simpan pelanggan');
                        const saved = await res.json();

                        this.form.pelanggan_id = saved.id;
                        this.selectedPelangganNames = saved.nama_pelanggan;
                        this.selectedPelangganLevel = (saved.level || 'retail').toString().toLowerCase();
                        this.form.is_walkin = false;

                        this.showModalTambahPelanggan = false;
                        this.updateAllItemPrices();

                        alert('Pelanggan berhasil ditambahkan!');
                    } catch (err) {
                        alert("Gagal menyimpan pelanggan baru");
                        console.error(err);
                    }
                },


                // === ITEMS / SCANNER ===
                async handleBarcode(e) {
                    const code = e.target.value.trim();
                    if (!code) return;

                    try {
                        const res = await fetch(`/items/barcode/${encodeURIComponent(code)}`);
                        if (!res.ok) {
                            alert(`Item dengan kode "${code}" tidak ditemukan. Tambahkan manual.`);
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
                                harga: 0,
                                stok: 0,
                                results: [],
                                harga_manual: false
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

                // === HARGA MANUAL ===
                updateManualPrice(idx, val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.items[idx].harga = parseInt(clean) || 0;
                    this.form.items[idx].harga_manual = true;
                    this.recalc();
                },

                resetManualPrice(idx) {
                    const item = this.form.items[idx];
                    if (!item) return;
                    item.harga_manual = false;
                    this.updateStockAndPrice(idx);
                },

                async fetchPriceForItem(idx) {
                    const item = this.form.items[idx];
                    if (!item || !item.satuan_id) return;

                    try {
                        const level = (this.selectedPelangganLevel || 'retail').toString().toLowerCase();
                        const res = await fetch(
                            `/items/price?satuan_id=${item.satuan_id}&level=${encodeURIComponent(level)}&is_walkin=${this.form.is_walkin ? 1 : 0}`
                        );
                        if (!res.ok) return;
                        const data = await res.json();
                        if (!item.harga_manual) {
                            item.harga = data.harga || item.harga || 0;
                            this.recalc();
                        }
                    } catch (err) {
                        console.error("Error fetchPriceForItem:", err);
                    }
                },

                // === TOTAL ===
                recalc() {
                    this.subTotal = this.form.items.reduce((sum, i) => {
                        const jumlah = parseFloat(i.jumlah) || 0;
                        const harga = parseFloat(i.harga) || 0;
                        return sum + (jumlah * harga);
                    }, 0);

                    const transport = this.form.mode === 'antar' ? (parseFloat(this.form.biaya_transport) || 0) : 0;
                    this.totalPembayaran = this.subTotal + transport;
                },

                formatRupiah(val) {
                    const num = parseFloat(val) || 0;
                    return new Intl.NumberFormat('id-ID').format(num);
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

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                updateTransport(val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.biaya_transport = parseInt(clean) || 0;
                    this.recalc();
                },

                // === VALIDASI & SIMPAN ===
                isValid() {
                    if (this.form.items.length === 0) return false;
                    return this.form.items.every(i => {
                        return i.item_id &&
                            i.gudang_id &&
                            i.satuan_id &&
                            i.jumlah > 0 &&
                            i.harga >= 0;
                    });
                },

                async save() {
                    if (!this.isValid()) {
                        alert('Mohon lengkapi semua data item penjualan.');
                        return;
                    }

                    for (let i = 0; i < this.form.items.length; i++) {
                        const item = this.form.items[i];
                        if (item.jumlah > item.stok) {
                            alert(`Stok tidak cukup untuk item: ${item.query}`);
                            return;
                        }
                    }

                    const payload = {
                        pelanggan_id: this.form.pelanggan_id,
                        no_faktur: this.form.no_faktur,
                        tanggal: this.form.tanggal,
                        deskripsi: this.form.deskripsi,
                        is_walkin: this.form.is_walkin,
                        biaya_transport: this.form.biaya_transport,
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        mode: this.form.mode,
                        status_bayar: 'unpaid',
                        is_draft: false,
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
                        const res = await fetch('/penjualan/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            alert('Gagal menyimpan penjualan: ' + (result.message || 'Unknown error'));
                            return;
                        }

                        // === Simpan sukses, munculkan popup print ===
                        this.savedPenjualanId = result.id; // simpan id buat ke route print
                        this.showPrintModal = true;

                    } catch (err) {
                        console.error('Error save:', err);
                        alert('Terjadi kesalahan saat menyimpan penjualan.');
                    }
                },


            }
        }
    </script>


@endsection
