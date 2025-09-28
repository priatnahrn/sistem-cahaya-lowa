@extends('layouts.app')

@section('title', 'Tambah Pembelian Baru')

@section('content')
    <div x-data="pembelianCreatePage()" x-init="init()" class="space-y-6">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Pembelian Baru
                </span>
            </div>
        </div>

        {{-- INFO UMUM --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Supplier --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <div class="relative" x-data="{ open: false }">
                        {{-- Search Icon --}}
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>

                        {{-- Input --}}
                        <input type="text" x-model="supplierQuery" @input.debounce.300ms="searchSupplier(); open = true"
                            @focus="open = true" @click.away="open = false" placeholder="Cari supplier..."
                            class="w-full pl-10 pr-8 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-700
                   focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">

                        {{-- Chevron Down --}}
                        <i
                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>

                        <input type="hidden" name="supplier_id" :value="form.supplier_id">

                        {{-- Dropdown suggestion --}}
                        <div x-show="open && supplierQuery.length >= 2" x-cloak x-transition
                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2">
                                {{-- Jika tidak ada hasil --}}
                                <div x-show="supplierResults.length === 0"
                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                    Tidak ada supplier ditemukan
                                </div>

                                {{-- Hasil --}}
                                <template x-for="s in supplierResults" :key="s.id">
                                    <div @click="selectSupplier(s); open=false"
                                        class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded">
                                        <div class="font-medium" x-text="s.nama_supplier"></div>
                                        <div class="text-xs text-slate-500" x-text="s.kontak"></div>
                                    </div>
                                </template>
                            </div>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Nomor Faktur --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Nomor Faktur</label>
                    <input type="text" x-model="form.no_faktur" readonly
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-slate-700 bg-slate-50">
                </div>
                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Tanggal</label>
                    <input type="date" x-model="form.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-slate-700">
                </div>
                {{-- Deskripsi --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Deskripsi</label>
                    <input type="text" x-model="form.deskripsi" placeholder="Opsional"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-slate-700">
                </div>
            </div>
        </div>

        {{-- DAFTAR ITEM --}}
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
                                {{-- Item --}}
                                <td class="px-4 py-3">
                                    <div class="relative" x-data="{ open: false }">
                                        {{-- Search Icon --}}
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                        {{-- Input --}}
                                        <input type="text" x-model="item.query"
                                            @input.debounce.300ms="searchItem(idx); open = true" @focus="open = true"
                                            @click.away="open = false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-8 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                   focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">

                                        {{-- Chevron Down --}}
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>

                                        {{-- Dropdown suggestion --}}
                                        <div x-show="open && item.query.length >= 2 && !item.item_id" x-cloak x-transition
                                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <div class="p-2">
                                                <div x-show="item.results.length === 0"
                                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                    Tidak ada item ditemukan
                                                </div>

                                                <template x-for="r in item.results" :key="r.id">
                                                    <div @click="selectItem(idx, r); open = false"
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
                                <td class="px-4 py-3 text-center">
                                    <div class="relative">
                                        <select x-model="item.gudang_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm appearance-none">
                                            <option value="">Pilih</option>
                                            @foreach ($gudangs as $g)
                                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                                            @endforeach
                                        </select>
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </td>
                                {{-- Jumlah --}}
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="0" x-model.number="item.jumlah" @input="recalc"
                                        class="mx-auto w-20 text-center border border-gray-300 rounded-lg px-2 py-2 text-sm" />
                                </td>
                                {{-- Satuan --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="relative">
                                        <select x-model="item.satuan_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm appearance-none">
                                            <option value="">Pilih</option>
                                            <template x-for="s in item.satuans" :key="s.id">
                                                <option :value="s.id" x-text="s.nama_satuan"></option>
                                            </template>
                                        </select>
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </td>

                                {{-- Harga --}}
                                <td class="px-4 py-3">
                                    <div class="relative">
                                        <span
                                            class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateHarga(idx, $event.target.value)"
                                            class="pl-10 pr-3 w-full border border-gray-300 rounded-lg py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-200" />
                                    </div>
                                </td>

                                {{-- Total --}}
                                <td class="px-4 py-3 text-right">
                                    <div class="relative">
                                        <span
                                            class="absolute left-0 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <span class="pl-6"
                                            x-text="formatRupiah(item.jumlah * item.harga).replace('Rp', '')"></span>
                                    </div>
                                </td>

                                {{-- Aksi --}}
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
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </button>
            </div>
        </div>

        {{-- RINGKASAN --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6 ">
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Biaya Transportasi</div>
                    <div class="relative w-40">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="text" :value="formatRupiah(form.biaya_transport)"
                            @input="updateTransport($event.target.value)"
                            class="pl-10 pr-3 w-full border border-slate-200 rounded-lg px-2 py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-200" />
                    </div>

                </div>
                <div class="border-t border-slate-200 pt-4 mt-4"></div>
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-700 font-bold">TOTAL PEMBELIAN</div>
                    <div class="text-[#344579] text-xl font-extrabold tracking-wide">
                        Rp <span x-text="formatRupiah(totalPembayaran)"></span>
                    </div>
                </div>
                <div class="mt-5 flex gap-3 justify-end">
                    <a href="{{ route('pembelian.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100">Batal</a>
                    <button @click="save" type="button" :disabled="!isValid()"
                        class="px-4 py-2 rounded-lg w-full text-white"
                        :class="isValid() ? 'bg-[#344579] hover:bg-[#2d3e6f] cursor-pointer' : 'bg-gray-300 cursor-not-allowed'">
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $suppliersJson = $suppliers
            ->map(
                fn($s) => [
                    'id' => $s->id,
                    'nama_supplier' => $s->nama_supplier,
                    'kontak' => $s->kontak,
                ],
            )
            ->toArray();
    @endphp

    @php
        $itemsJson = $items
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'satuans' => $i->satuans
                        ->map(
                            fn($s) => [
                                'id' => $s->id,
                                'nama_satuan' => $s->nama_satuan,
                            ],
                        )
                        ->toArray(),
                    'satuan_default' => $i->satuan_default_id ?? null,
                ],
            )
            ->toArray();
    @endphp


    <script>
        function pembelianCreatePage() {
            return {
                supplierQuery: '',
                supplierResults: [],
                allSuppliers: @json($suppliersJson), // <-- load semua supplier di awal
                form: {
                    supplier_id: null,
                    no_faktur: '',
                    tanggal: '',
                    lunas: false,
                    deskripsi: '',
                    biaya_transport: 0,
                    items: []
                },

                init() {
                    this.addItem();
                    this.form.tanggal = new Date().toISOString().split('T')[0];

                    // pakai preview dari backend, bukan random
                    this.form.no_faktur = @json($noFakturPreview);
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
                    ).slice(0, 20); // batasi hasil biar gak kepanjangan
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
                        harga: 0
                    });
                },

                allItems: @json($itemsJson),

                searchItem(idx) {
                    const q = this.form.items[idx].query.toLowerCase();
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }
                    this.form.items[idx].results = this.allItems.filter(r =>
                        r.nama_item.toLowerCase().includes(q) ||
                        r.kode_item.toLowerCase().includes(q)
                    ).slice(0, 20); // batasi max 20 hasil
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

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
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
                    if (!this.isValid()) return;

                    const payload = JSON.parse(JSON.stringify(this.form));
                    payload.sub_total = this.subTotal;
                    payload.total = this.totalPembayaran;
                    payload.status = this.form.lunas ? 'paid' : 'unpaid';
                    payload.biaya_transport = parseInt(this.form.biaya_transport) || 0;
                    delete payload.lunas;

                    payload.items = this.form.items.map(i => ({
                        item_id: i.item_id,
                        gudang_id: i.gudang_id,
                        satuan_id: i.satuan_id,
                        jumlah: i.jumlah,
                        harga: parseInt(i.harga) || 0
                    }));

                    console.log("Payload dikirim:", payload);

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
                        window.location.href = "{{ route('pembelian.index') }}";
                    } else {
                        const errText = await res.text();
                        console.error("Gagal simpan:", errText);
                        alert("Gagal menyimpan! Cek console untuk detail error.");
                    }
                }
            }
        }
    </script>
@endsection
