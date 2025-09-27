@extends('layouts.app')

@section('title', 'Tambah Penjualan Baru')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div x-data="penjualanCreatePage()" x-init="init()" class="space-y-6">

        {{-- breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Penjualan Baru
                </span>
            </div>
        </div>

        {{-- input scanner (hidden, fokus otomatis) --}}
        <input type="text" id="barcodeInput" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none">

        {{-- info card --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 shadow-sm">
            <div class="space-y-4">
                {{-- pelanggan --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Pelanggan</label>
                    <div class="relative flex items-center gap-3">
                        <div class="flex-1 relative">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" x-model="pelangganQuery" @input.debounce.300ms="searchPelanggan"
                                placeholder="Cari pelanggan (ketik minimal 2 huruf)"
                                class="w-full pl-12 pr-4 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400">
                            <input type="hidden" name="pelanggan_id" :value="form.pelanggan_id">

                            <ul x-show="pelangganQuery.length >= 2 && !form.pelanggan_id" x-cloak
                                class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow text-sm max-h-56 overflow-auto">
                                <template x-if="pelangganResults.length === 0">
                                    <li class="px-3 py-2 text-gray-500 italic">Data pelanggan tidak ditemukan</li>
                                </template>
                                <template x-for="p in pelangganResults" :key="p.id">
                                    <li @click="selectPelanggan(p)" class="px-3 py-2 cursor-pointer hover:bg-gray-100">
                                        <div class="font-medium" x-text="p.nama_pelanggan"></div>
                                        <small class="text-gray-500 block" x-text="p.kontak ?? ''"></small>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        {{-- tombol tambah pelanggan --}}
                        <button type="button" @click="openTambahPelanggan()"
                            class="px-3 py-2 rounded border border-slate-200 text-sm">Tambah Pelanggan</button>
                    </div>
                </div>

                {{-- mode antar/ambil + nomor faktur & tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Mode Pengambilan</label>
                        <select x-model="form.mode" @change="onModeChange" class="w-full px-3 py-2 border rounded">
                            <option value="ambil">Ambil Sendiri</option>
                            <option value="antar">Antar Barang</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Nomor Faktur</label>
                        <input type="text" x-model="form.no_faktur" readonly
                            class="w-full px-3 py-2 border rounded bg-slate-50">
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Tanggal</label>
                        <input type="date" x-model="form.tanggal" class="w-full px-3 py-2 border rounded">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-500 mb-2">Deskripsi (opsional)</label>
                    <input type="text" x-model="form.deskripsi" class="w-full px-3 py-2 border rounded">
                </div>
            </div>
        </div>

        <!-- Input simulasi scanner -->
        <div class="mb-4">
            <label class="block text-sm text-slate-500 mb-2">Simulasi Scan Barcode</label>
            <input type="text" placeholder="Ketik kode barcode lalu tekan Enter"
                @keydown.enter.prevent="handleBarcode($event)" class="w-80 px-3 py-2 border rounded" />
        </div>

        {{-- daftar item --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
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
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                <td class="px-4 py-3 text-center" x-text="idx+1"></td>
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="item.query" @input.debounce.300ms="searchItem(idx)"
                                            placeholder="Cari item (manual, min 2 huruf)"
                                            class="w-full pl-10 pr-3 py-2 rounded-lg border">
                                        <ul x-show="item.query.length >= 2 && !item.item_id" x-cloak
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border rounded shadow text-sm max-h-56 overflow-auto">
                                            <template x-if="item.results.length === 0">
                                                <li class="px-3 py-2 text-gray-500 italic">Data item tidak ditemukan</li>
                                            </template>
                                            <template x-for="r in item.results" :key="r.id">
                                                <li @click="selectItem(idx, r)"
                                                    class="px-3 py-2 cursor-pointer hover:bg-gray-100">
                                                    <div class="font-medium" x-text="r.nama_item"></div>
                                                    <small class="text-gray-500 block" x-text="r.kode_item ?? ''"></small>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <select x-model.number="item.gudang_id" @change="onGudangChange(idx)"
                                        class="w-full border rounded px-3 py-2">
                                        <option value="">Pilih</option>
                                        @foreach ($gudangs as $g)
                                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                        @endforeach
                                    </select>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="0" x-model.number="item.jumlah" @input="recalc"
                                        class="mx-auto w-20 text-center border rounded px-2 py-2">
                                    <div class="text-xs mt-1"
                                        :class="(item.jumlah > (item.stok ?? 0)) ? 'text-rose-600 font-bold' : 'text-slate-400'">
                                        Stok: <span x-text="item.stok ?? 0"></span> <span
                                            x-text="item.satuan_nama ?? ''"></span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <select x-model.number="item.satuan_id" @change="onSatuanChange(idx)"
                                        class="w-full border rounded px-3 py-2">
                                        <option value="">Pilih</option>
                                        <template x-for="s in item.satuans" :key="s.id">
                                            <option :value="s.id" x-text="s.nama_satuan"></option>
                                        </template>
                                    </select>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateHarga(idx,$event.target.value)"
                                            class="pl-10 pr-3 w-full border rounded py-2 text-right" />
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="relative">
                                        <span
                                            class="absolute left-0 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <span class="pl-6"
                                            x-text="formatRupiah((item.jumlah||0) * (item.harga||0))"></span>
                                    </div>
                                </td>

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

            <div class="m-4">
                <button type="button" @click="addItem"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed bg-slate-50">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </button>
            </div>
        </div>

        {{-- ringkasan --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border rounded-2xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">Rp <span x-text="formatRupiah(subTotal)"></span></div>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Biaya Transportasi</div>
                    <div class="relative w-40">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="text" :value="formatRupiah(form.biaya_transport)"
                            @input="updateTransport($event.target.value)" :disabled="form.mode === 'ambil'"
                            class="pl-10 pr-3 w-full border rounded px-2 py-2 text-right" />
                    </div>
                </div>

                <div class="border-t pt-4 mt-4"></div>

                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-700 font-bold">TOTAL PENJUALAN</div>
                    <div class="text-[#344579] text-xl font-extrabold tracking-wide">Rp <span
                            x-text="formatRupiah(totalPembayaran)"></span></div>
                </div>

                <div class="mt-5 flex gap-3 justify-end">
                    <a href="{{ route('penjualan.index') }}" class="px-4 py-2 rounded-lg border text-slate-600">Batal</a>
                    <button type="button" @click="save" :disabled="!isValid()"
                        class="px-4 py-2 rounded-lg w-full text-white"
                        :class="isValid() ? 'bg-[#344579]' : 'bg-gray-300'">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function penjualanCreatePage() {
            return {
                pelangganQuery: '',
                pelangganResults: [],

                subTotal: 0,
                totalPembayaran: 0,
                form: {
                    pelanggan_id: null,
                    mode: 'ambil', // 'ambil' or 'antar'
                    no_faktur: '{{ $noFakturPreview ?? date('dmY') . '-001' }}',
                    tanggal: '{{ date('Y-m-d') }}',
                    deskripsi: '',
                    biaya_transport: 0,
                    items: []
                },

                // modal state
                tambahPelangganModal: false,
                newPelanggan: {
                    nama_pelanggan: '',
                    kontak: '',
                    alamat: '',
                    level: 'retail'
                },
                tambahPelangganErrors: {},

                // keep track of selected pelanggan level
                selectedPelangganLevel: null,

                allowAnonymous: true, // jika mau izinkan customer default tanpa pilih pelanggan

                init() {
                    this.addItem();
                    this.recalc();
                    this.$nextTick(() => {
                        if (this.$refs.barcodeInput) this.$refs.barcodeInput.focus();
                    });
                },

                /* === PELANGGAN === */
                async searchPelanggan() {
                    if (this.pelangganQuery.length < 2) {
                        this.pelangganResults = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/pelanggan/search?q=${encodeURIComponent(this.pelangganQuery)}`);
                        this.pelangganResults = await res.json();
                    } catch (e) {
                        console.error(e);
                        this.pelangganResults = [];
                    }
                },

                selectPelanggan(p) {
                    this.form.pelanggan_id = p.id;
                    this.pelangganQuery = p.nama_pelanggan;
                    this.pelangganResults = [];
                    this.updateAllItemPrices();
                },

                openTambahPelanggan() {
                    this.tambahPelangganModal = true;
                    this.newPelanggan = {
                        nama_pelanggan: '',
                        kontak: '',
                        alamat: '',
                        level: 'retail'
                    };
                    this.tambahPelangganErrors = {};

                    this.$nextTick(() => {
                        const nama = prompt('Nama Pelanggan (wajib):');
                        if (!nama) return;
                        const kontak = prompt('Kontak (opsional):') || '';
                        const level = prompt('Level (retail/partai_kecil/grosir) [retail]:') || 'retail';

                        fetch('/pelanggan', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify({
                                nama_pelanggan: nama,
                                kontak: kontak,
                                alamat: '',
                                level: level
                            })
                        }).then(async r => {
                            if (r.status === 201) {
                                const js = await r.json();
                                this.form.pelanggan_id = js.pelanggan.id;
                                this.pelangganQuery = js.pelanggan.nama_pelanggan;
                                this.updateAllItemPrices();
                                alert('Pelanggan berhasil ditambahkan dan dipilih.');
                            } else if (r.status === 422) {
                                const js = await r.json();
                                alert('Validasi gagal: ' + JSON.stringify(js.errors));
                            } else {
                                const t = await r.text();
                                console.error(t);
                                alert('Gagal menambah pelanggan');
                            }
                        }).catch(e => {
                            console.error(e);
                            alert('Error saat menambah pelanggan');
                        });
                    });
                },

                onModeChange() {
                    if (this.form.mode === 'ambil') {
                        this.form.biaya_transport = 0;
                    }
                    this.updateAllItemPrices();
                },

                /* === ITEMS (manual input) === */
                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '{{ $gudangs->first()->id ?? '' }}',
                        satuan_id: '',
                        satuans: [],
                        jumlah: 1,
                        harga: 0,
                        price_tiers: null,
                        stok: 0,
                        satuan_nama: ''
                    });
                },

                async searchItem(idx) {
                    const q = this.form.items[idx].query;
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }
                    try {
                        const res = await fetch(`/items/search?q=${encodeURIComponent(q)}`);
                        const data = await res.json();
                        this.form.items[idx].results = data;
                    } catch (e) {
                        console.error(e);
                        this.form.items[idx].results = [];
                    }
                },

                async selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.satuans = item.satuans || [];

                    if (item.satuan_default) row.satuan_id = item.satuan_default;
                    else if (row.satuans.length) row.satuan_id = row.satuans[0].id;

                    await this.fetchPricesForItem(idx);
                    await this.fetchStockForItem(idx);
                    this.recalc();
                },

                async onGudangChange(idx) {
                    await this.fetchStockForItem(idx);
                },

                async onSatuanChange(idx) {
                    await this.fetchPricesForItem(idx);
                    await this.fetchStockForItem(idx);
                },

                async fetchPricesForItem(idx) {
                    const it = this.form.items[idx];
                    if (!it.item_id || !it.satuan_id) return;
                    try {
                        const res = await fetch(`/items/${it.item_id}/prices?satuan_id=${it.satuan_id}`);
                        if (!res.ok) return;
                        const json = await res.json();
                        it.price_tiers = {
                            harga_retail: Number(json.harga_retail || 0),
                            partai_kecil: Number(json.partai_kecil || 0),
                            harga_grosir: Number(json.harga_grosir || 0),
                            last_purchase: Number(json.last_purchase_price || 0)
                        };
                        it.harga = this.resolvePriceForItem(it);
                    } catch (e) {
                        console.error(e);
                    }
                },

                async fetchStockForItem(idx) {
                    const it = this.form.items[idx];
                    if (!it.item_id || !it.gudang_id || !it.satuan_id) {
                        it.stok = 0;
                        it.satuan_nama = '';
                        return;
                    }
                    try {
                        const res = await fetch(
                            `/items/${it.item_id}/stock?gudang_id=${it.gudang_id}&satuan_id=${it.satuan_id}`);
                        if (!res.ok) return;
                        const json = await res.json();
                        it.stok = Number(json.stok || 0);
                        it.satuan_nama = json.satuan || '';
                    } catch (e) {
                        console.error(e);
                        it.stok = 0;
                        it.satuan_nama = '';
                    }
                },

                resolvePriceForItem(it) {
                    const t = it.price_tiers || {};
                    const level = this.selectedPelangganLevel || 'retail';
                    if (level === 'retail') return Number(t.harga_retail || t.partai_kecil || t.harga_grosir || 0);

                    if (this.form.mode === 'antar') {
                        return Number(t.harga_grosir || t.partai_kecil || t.harga_retail || 0);
                    } else {
                        return Number(t.partai_kecil || t.harga_retail || t.harga_grosir || 0);
                    }
                },

                updateAllItemPrices() {
                    if (this.form.pelanggan_id) {
                        fetch(`/pelanggan/search?q=${encodeURIComponent(this.pelangganQuery)}`)
                            .then(r => r.json())
                            .then(list => {
                                const p = list.find(x => x.id == this.form.pelanggan_id);
                                if (p) this.selectedPelangganLevel = p.level || 'retail';
                                else this.selectedPelangganLevel = 'retail';
                            }).finally(() => {
                                this.form.items.forEach((it, idx) => {
                                    if (it.price_tiers) it.harga = this.resolvePriceForItem(it);
                                });
                                this.recalc();
                            });
                    } else {
                        this.selectedPelangganLevel = 'retail';
                        this.form.items.forEach((it, idx) => {
                            if (it.price_tiers) it.harga = this.resolvePriceForItem(it);
                        });
                        this.recalc();
                    }
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                /* === BARCODE SCANNER === */
                async handleBarcode(e) {
                    const kode = e.target.value.trim();
                    if (!kode) return;

                    try {
                        const res = await fetch(`/items/by-barcode/${kode}`);
                        if (!res.ok) {
                            alert('Item tidak ditemukan untuk barcode: ' + kode);
                            e.target.value = '';
                            return;
                        }
                        const item = await res.json();

                        let existing = this.form.items.find(it => it.item_id === item.id);
                        if (existing) {
                            existing.jumlah += 1;
                            this.recalc();
                            e.target.value = '';
                            return;
                        }

                        this.form.items.push({
                            item_id: item.id,
                            query: item.nama_item,
                            results: [],
                            gudang_id: '{{ $gudangs->first()->id ?? '' }}',
                            satuan_id: item.satuan_default ?? (item.satuans?.[0]?.id ?? ''),
                            satuans: item.satuans || [],
                            jumlah: 1,
                            harga: 0,
                            price_tiers: null,
                            stok: 0,
                            satuan_nama: ''
                        });

                        const idx = this.form.items.length - 1;
                        await this.fetchPricesForItem(idx);
                        await this.fetchStockForItem(idx);
                        this.recalc();

                        e.target.value = '';
                    } catch (err) {
                        console.error(err);
                        alert('Gagal mengambil item barcode');
                    }
                },

                /* === UTIL === */
                updateHarga(idx, val) {
                    let num = String(val).replace(/[^0-9]/g, '');
                    this.form.items[idx].harga = parseInt(num) || 0;
                    this.recalc();
                },

                updateTransport(val) {
                    let num = String(val).replace(/[^0-9]/g, '');
                    this.form.biaya_transport = parseInt(num) || 0;
                    this.recalc();
                },

                recalc() {
                    this.subTotal = this.form.items.reduce((sum, i) =>
                        sum + (Number(i.jumlah || 0) * Number(i.harga || 0)), 0);
                    this.totalPembayaran = this.subTotal + (Number(this.form.biaya_transport || 0));
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0
                    }).format(n || 0);
                },

                /* === VALIDASI + SAVE === */
                isValid() {
                    if (!this.form.pelanggan_id && !this.allowAnonymous) {
                        return false;
                    }
                    for (let it of this.form.items) {
                        if (!it.item_id || !it.gudang_id || !it.satuan_id) return false;
                        if (!it.jumlah || Number(it.jumlah) <= 0) return false;
                        if (!it.harga || Number(it.harga) <= 0) return false;
                        if ((it.stok || 0) < Number(it.jumlah)) return false;
                    }
                    return true;
                },

                async save() {
                    if (!this.isValid()) {
                        alert(
                            'Form belum valid. Pastikan pelanggan dipilih atau izinkan anonymous, semua item valid dan stok mencukupi.'
                            );
                        return;
                    }

                    const payload = {
                        pelanggan_id: this.form.pelanggan_id || null,
                        no_faktur: this.form.no_faktur,
                        tanggal: this.form.tanggal,
                        deskripsi: this.form.deskripsi,
                        biaya_transport: Number(this.form.biaya_transport || 0),
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        status_bayar: 'belum lunas',
                        status_kirim: '-',
                        items: this.form.items.map(i => ({
                            item_id: i.item_id,
                            gudang_id: i.gudang_id,
                            satuan_id: i.satuan_id,
                            jumlah: Number(i.jumlah),
                            harga: Number(i.harga),
                        }))
                    };

                    try {
                        const res = await fetch("{{ route('penjualan.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            alert('Penjualan berhasil disimpan');
                            window.location.href = "{{ route('penjualan.index') }}";
                        } else {
                            const js = await res.json().catch(() => null);
                            alert('Gagal menyimpan: ' + (js?.message || 'Cek console'));
                            console.error(await res.text());
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Terjadi kesalahan saat menyimpan.');
                    }
                }
            }
        }
    </script>

@endsection
