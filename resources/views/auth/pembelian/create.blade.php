@extends('layouts.app')

@section('title', 'Tambah Pembelian Baru')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Alpine cloak fix --}}
    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Hilangkan spinner di Chrome, Safari, Edge (WebKit/Blink) */
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hilangkan spinner di Firefox */
        .no-spinner {
            -moz-appearance: textfield;
        }

        /* Hilangkan tombol di IE / old Edge */
        .no-spinner::-ms-clear,
        .no-spinner::-ms-expand {
            display: none;
        }
    </style>

    {{-- Root Alpine Component --}}
    <div x-data="pembelianCreatePage()" x-init="init()" class="space-y-6">

        {{-- 🔔 Toast Notification --}}
        <div x-show="showNotif" x-transition class="fixed top-5 right-5 z-50">
            <div :class="{
                'bg-green-500': notifType === 'success',
                'bg-red-500': notifType === 'error',
                'bg-blue-500': notifType === 'info'
            }"
                class="text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 min-w-[250px]">
                <template x-if="notifType === 'success'">
                    <i class="fa-solid fa-circle-check"></i>
                </template>
                <template x-if="notifType === 'error'">
                    <i class="fa-solid fa-circle-xmark"></i>
                </template>
                <template x-if="notifType === 'info'">
                    <i class="fa-solid fa-circle-info"></i>
                </template>
                <span x-text="notifMessage"></span>
            </div>
        </div>

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('pembelian.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- 📦 Card Utama - INFO UMUM --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">

                {{-- Input Supplier --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                        <div class="relative" @click.away="openSupplierResults = false">
                            <input type="text" x-model="supplierQuery"
                                @input.debounce.300ms="
                                    if (supplierQuery.length >= 2) {
                                        searchSupplier();
                                        openSupplierResults = true;
                                    } else {
                                        form.supplier_id = null;
                                        supplierResults = [];
                                        openSupplierResults = false;
                                    }
                                "
                                @focus="openSupplierResults = (supplierQuery.length >= 2)"
                                placeholder="Cari supplier"
                                class="w-full pl-4 pr-12 py-2.5 rounded-lg border border-slate-300
                                    focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                            {{-- Icon pencarian --}}
                            <span x-show="!form.supplier_id" x-cloak x-transition.opacity.duration.150ms
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            {{-- Dropdown hasil pencarian --}}
                            <div x-show="openSupplierResults && supplierQuery.length >= 2" x-cloak
                                class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200
                                   rounded-lg shadow-lg text-sm max-h-56 overflow-auto">

                                <template x-if="supplierResults.length > 0">
                                    <ul>
                                        <template x-for="s in supplierResults" :key="s.id">
                                            <li @click="selectSupplier(s); openSupplierResults = false"
                                                class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition border-b border-slate-100 last:border-b-0">
                                                <div class="font-medium text-slate-800" x-text="s.nama_supplier"></div>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <small class="text-slate-500" x-text="s.kontak || '-'"></small>
                                                </div>
                                            </li>
                                        </template>
                                    </ul>
                                </template>

                                <template x-if="supplierResults.length === 0">
                                    <div class="px-4 py-3 text-gray-500 italic text-center">
                                        <i class="fa-solid fa-store-slash mr-1"></i>
                                        Supplier tidak ditemukan
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Checkbox Lunas --}}
                    <div class="flex items-center gap-2 mt-6">
                        <input type="checkbox" id="lunas" x-model="form.lunas"
                            class="w-5 h-5 text-indigo-600 rounded border-gray-300">
                        <label for="lunas" class="text-sm text-slate-600">Lunas</label>
                    </div>
                </div>

                {{-- No Faktur, Tanggal, Deskripsi --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">No. Faktur</label>
                        <input type="text" x-model="form.no_faktur" readonly
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi</label>
                        <input type="text" x-model="form.deskripsi" placeholder="Opsional"
                            class="w-full px-3 py-2.5 border border-slate-300 rounded-lg
                                      focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    </div>
                </div>
            </div>
        </div>

        {{-- === TABEL ITEM === --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800">Daftar Item Pembelian</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 w-12 text-center">No.</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 w-40 text-center">Gudang</th>
                            <th class="px-4 py-3 w-28 text-center">Jumlah</th>
                            <th class="px-4 py-3 w-32 text-center">Satuan</th>
                            <th class="px-4 py-3 w-40 text-center">Harga</th>
                            <th class="px-4 py-3 w-40 text-center">Total</th>
                            <th class="px-2 py-3 w-12"></th>
                        </tr>
                    </thead>

                    <tbody class="align-middle">
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100 transition">
                                {{-- Nomor urut --}}
                                <td class="px-5 py-4 text-center font-medium align-middle" x-text="idx + 1"></td>

                                {{-- Item --}}
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <input type="text" x-model="item.query"
                                            @input.debounce.300ms="handleItemInput(idx)"
                                            @focus="handleItemFocus(idx)"
                                            @click="handleItemClick(idx)"
                                            @keydown.escape="handleItemEscape(idx)"
                                            placeholder="Cari item"
                                            class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                        {{-- Icon pencarian --}}
                                        <span x-show="!item.item_id" x-cloak x-transition.opacity.duration.150ms
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        {{-- Dropdown hasil pencarian --}}
                                        <div x-show="item._dropdownOpen && item.query.length >= 2 && !item.item_id"
                                            x-cloak x-transition
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto text-sm">

                                            <div x-show="item.results.length === 0"
                                                class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                Tidak ada item ditemukan
                                            </div>

                                            <template x-for="r in item.results" :key="r.id">
                                                <div @click="selectItem(idx, r); item._dropdownOpen = false;"
                                                    class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded transition">
                                                    <div class="font-medium" x-text="r.nama_item"></div>
                                                    <div class="text-xs text-slate-500" x-text="r.kode_item"></div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                {{-- Gudang --}}
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <select x-model="item.gudang_id"
                                            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-slate-700 
                                            appearance-none focus:outline-none focus:ring-2 focus:ring-[#344579]/20 
                                            focus:border-[#344579] transition">
                                            <option value="">Pilih</option>
                                            @foreach ($gudangs as $g)
                                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                            @endforeach
                                        </select>
                                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </td>

                                {{-- Jumlah --}}
                                <td class="px-5 py-4 text-center align-middle">
                                    <input type="text" 
                                        :value="item.jumlah ? formatJumlah(item.jumlah) : ''"
                                        @input="updateJumlahFormatted(idx, $event.target.value)"
                                        class="no-spinner w-24 text-center border border-slate-300 rounded-lg px-2 py-2.5 
                                               focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        inputmode="numeric" pattern="[0-9]*" />
                                </td>

                                {{-- Satuan --}}
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <select x-model="item.satuan_id"
                                            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-slate-700 
                                            appearance-none focus:outline-none focus:ring-2 focus:ring-[#344579]/20 
                                            focus:border-[#344579] transition">
                                            <option value="">Pilih</option>
                                            <template x-for="s in item.satuans" :key="s.id">
                                                <option :value="s.id" x-text="s.nama_satuan"></option>
                                            </template>
                                        </select>
                                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </td>

                                {{-- Harga --}}
                                <td class="px-5 py-4 text-right align-middle">
                                    <div class="relative">
                                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateHarga(idx, $event.target.value)"
                                            class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg py-2.5 
                                            focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                    </div>
                                </td>

                                {{-- Total --}}
                                <td class="px-5 py-4 text-right font-semibold text-slate-800 align-middle whitespace-nowrap">
                                    Rp <span x-text="formatRupiah(item.jumlah * item.harga)"></span>
                                </td>

                                {{-- Hapus --}}
                                <td class="px-3 py-4 text-center align-middle">
                                    <button type="button" @click="removeItem(idx)"
                                        class="text-rose-600 hover:text-rose-800 transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                {{-- Button Tambah Item Manual --}}
                <div class="m-4">
                    <button type="button" @click="addItem"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                        <i class="fa-solid fa-plus"></i> Tambah Item Baru
                    </button>
                </div>
            </div>
        </div>

        {{-- === RINGKASAN PEMBELIAN === --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6 transition-all duration-300">

                {{-- Sub Total --}}
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>

                {{-- Biaya Transportasi --}}
                <div class="mb-4">
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

                {{-- TOTAL PEMBELIAN --}}
                <div class="flex justify-between items-center mb-6">
                    <div class="text-slate-700 font-bold text-lg">TOTAL PEMBELIAN</div>
                    <div class="text-[#334976] text-2xl font-extrabold tracking-wide">
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
                    <a href="{{ route('pembelian.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-white hover:border-slate-400 transition-all font-medium">
                        Batal
                    </a>

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
    </div>

    @php
        $suppliersJson = $suppliers
            ->map(fn($s) => [
                'id' => $s->id,
                'nama_supplier' => $s->nama_supplier,
                'kontak' => $s->kontak,
            ])
            ->toArray();

        $itemsJson = $items
            ->map(fn($i) => [
                'id' => $i->id,
                'kode_item' => $i->kode_item,
                'nama_item' => $i->nama_item,
                'satuans' => $i->satuans
                    ->map(fn($s) => [
                        'id' => $s->id,
                        'nama_satuan' => $s->nama_satuan,
                    ])
                    ->toArray(),
                'satuan_default' => $i->satuan_default_id ?? null,
            ])
            ->toArray();
    @endphp

    <script>
        function pembelianCreatePage() {
            return {
                supplierQuery: '',
                supplierResults: [],
                openSupplierResults: false,
                allSuppliers: @json($suppliersJson),
                allItems: @json($itemsJson),

                form: {
                    supplier_id: null,
                    no_faktur: '',
                    tanggal: '',
                    lunas: false,
                    deskripsi: '',
                    biaya_transport: 0,
                    items: []
                },

                subTotal: 0,
                totalPembayaran: 0,

                // Notifikasi
                notifMessage: '',
                notifType: '',
                showNotif: false,

                init() {
                    this.addItem();
                    this.form.tanggal = new Date().toISOString().split('T')[0];
                    this.form.no_faktur = @json($noFakturPreview);
                },

                notify(msg, type = 'info') {
                    this.notifMessage = msg;
                    this.notifType = type;
                    this.showNotif = true;
                    setTimeout(() => (this.showNotif = false), 3000);
                },

                searchSupplier() {
                    const q = this.supplierQuery.toLowerCase();
                    if (q.length < 2) {
                        this.supplierResults = [];
                        return;
                    }
                    this.supplierResults = this.allSuppliers.filter(s =>
                        s.nama_supplier.toLowerCase().includes(q) ||
                        (s.kontak && s.kontak.toLowerCase().includes(q))
                    ).slice(0, 20);
                },

                selectSupplier(s) {
                    this.form.supplier_id = s.id;
                    this.supplierQuery = s.nama_supplier;
                    this.supplierResults = [];
                },

                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '',
                        satuan_id: '',
                        satuans: [],
                        jumlah: 1,
                        harga: 0,
                        _dropdownOpen: false
                    });
                },

                searchItem(idx) {
                    const q = this.form.items[idx].query.toLowerCase();
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }
                    this.form.items[idx].results = this.allItems.filter(r =>
                        r.nama_item.toLowerCase().includes(q) ||
                        r.kode_item.toLowerCase().includes(q)
                    ).slice(0, 20);
                },

                selectItem(idx, item) {
                    this.form.items[idx].item_id = item.id;
                    this.form.items[idx].query = item.nama_item;
                    this.form.items[idx].results = [];
                    this.form.items[idx].satuans = item.satuans || [];

                    if (item.satuan_default) {
                        this.form.items[idx].satuan_id = item.satuan_default;
                    } else if (item.satuans && item.satuans.length > 0) {
                        this.form.items[idx].satuan_id = item.satuans[0].id;
                    }

                    if (!this.form.items[idx].gudang_id) {
                        this.form.items[idx].gudang_id = '{{ $gudangs->first()->id ?? '' }}';
                    }
                },

                handleItemInput(idx) {
                    const item = this.form.items[idx];
                    if (item.query.length >= 2) {
                        this.searchItem(idx);
                        item._dropdownOpen = true;
                    } else {
                        item.item_id = null;
                        item.results = [];
                        item._dropdownOpen = false;
                    }
                },

                handleItemFocus(idx) {
                    const item = this.form.items[idx];
                    if (item.query && item.query.length >= 2) {
                        item._dropdownOpen = true;
                    }
                },

                handleItemClick(idx) {
                    const item = this.form.items[idx];
                    if (item.query && item.query.length >= 2) {
                        item._dropdownOpen = true;
                    }
                },

                handleItemEscape(idx) {
                    this.form.items[idx]._dropdownOpen = false;
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                updateJumlahFormatted(idx, val) {
                    val = (val || '').toString();
                    if (val.startsWith(',')) val = '0' + val;
                    val = val.replace(/[^0-9,]/g, '');

                    let parts = val.split(',');
                    if (parts.length > 2) {
                        parts = [parts[0], parts.slice(1).join('')];
                    }

                    parts[0] = parts[0].replace(/^0+(?=\d)/, '');
                    if (parts[1]) {
                        parts[1] = parts[1].replace(/[^0-9]/g, '');
                    }

                    const numericStr = (parts[0] ? parts[0].replace(/\./g, '') : '0') + (parts[1] ? '.' + parts[1] : '');
                    const numeric = parseFloat(numericStr) || 0;
                    this.form.items[idx].jumlah = numeric;
                    this.recalc();
                },

                formatJumlah(val) {
                    if (val == null || val === '') return '';
                    const s = val.toString();
                    const parts = s.split('.');
                    const intPart = (parts[0] || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const decPart = parts[1] || '';
                    return decPart ? `${intPart},${decPart}` : intPart;
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

                recalc() {
                    this.subTotal = this.form.items.reduce((sum, i) => sum + (i.jumlah * i.harga), 0);
                    this.totalPembayaran = this.subTotal + (this.form.biaya_transport || 0);
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0
                    }).format(n || 0);
                },

                isValid() {
                    if (!this.form.supplier_id) return false;
                    if (this.form.items.length === 0) return false;

                    for (let it of this.form.items) {
                        if (!it.item_id) return false;
                        if (!it.gudang_id) return false;
                        if (!it.satuan_id) return false;
                        if (!it.jumlah || it.jumlah <= 0) return false;
                        if (!it.harga || it.harga <= 0) return false;
                    }
                    return true;
                },

                async save() {
                    if (!this.isValid()) {
                        this.notify('Mohon lengkapi semua data pembelian.', 'error');
                        return;
                    }

                    const payload = {
                        supplier_id: this.form.supplier_id,
                        tanggal: this.form.tanggal,
                        deskripsi: this.form.deskripsi,
                        biaya_transport: parseInt(this.form.biaya_transport) || 0,
                        status: this.form.lunas ? 'paid' : 'unpaid',
                        items: this.form.items.map(i => ({
                            item_id: i.item_id,
                            gudang_id: i.gudang_id,
                            satuan_id: i.satuan_id,
                            jumlah: i.jumlah,
                            harga: parseInt(i.harga) || 0
                        }))
                    };

                    try {
                        const res = await fetch(`{{ route('pembelian.store') }}`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            this.notify('Pembelian berhasil disimpan!', 'success');
                            setTimeout(() => {
                                window.location.href = "{{ route('pembelian.index') }}";
                            }, 1000);
                        } else {
                            const err = await res.json().catch(() => null);
                            console.error("Gagal simpan:", err);

                            if (err?.errors) {
                                Object.values(err.errors).flat().forEach(msg => {
                                    this.notify(msg, 'error');
                                });
                            } else {
                                this.notify('Gagal menyimpan pembelian. Silakan coba lagi.', 'error');
                            }
                        }
                    } catch (error) {
                        console.error('Error save:', error);
                        this.notify('Terjadi kesalahan saat menyimpan pembelian.', 'error');
                    }
                }
            }
        }
    </script>
@endsection