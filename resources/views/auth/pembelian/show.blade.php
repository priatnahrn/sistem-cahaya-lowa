@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <div x-data="pembelianEditPage()" class="space-y-6">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    {{ $pembelian->no_faktur }}
                </span>
            </div>
        </div>

        {{-- INFO UMUM --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4  space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Supplier --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Supplier</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="supplierQuery" @input.debounce.300ms="searchSupplier"
                            placeholder="Cari supplier"
                            class="w-full pl-12 pr-4 py-2 rounded-lg border border-gray-300 text-slate-600 placeholder-slate-400">
                        <input type="hidden" name="supplier_id" :value="form.supplier_id">
                        <ul x-show="supplierResults.length"
                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow text-sm max-h-56 overflow-auto">
                            <template x-for="s in supplierResults" :key="s.id">
                                <li @click="selectSupplier(s)" class="px-3 py-2 cursor-pointer hover:bg-gray-100">
                                    <span x-text="s.nama_supplier"></span>
                                    <small class="text-gray-500" x-text="s.kontak"></small>
                                </li>
                            </template>
                        </ul>
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
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-slate-50 text-slate-700">
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
                                    <div class="relative">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="item.query" @input.debounce.300ms="searchItem(idx)"
                                            placeholder="Cari item"
                                            class="w-full pl-10 pr-3 py-2 rounded-lg border border-gray-300 text-sm">
                                        <ul x-show="item.results.length"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-gray-300 rounded-lg shadow text-sm max-h-56 overflow-auto">
                                            <template x-for="r in item.results" :key="r.id">
                                                <li @click="selectItem(idx, r)"
                                                    class="px-3 py-2 cursor-pointer hover:bg-gray-100">
                                                    <span x-text="r.nama_item"></span>
                                                    <small class="text-gray-500" x-text="r.kode_item"></small>
                                                </li>
                                            </template>
                                        </ul>
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
                                    <input type="number" min="0" step="0.01" :value="formatJumlah(item.jumlah)"
                                        @input="item.jumlah = parseFloat($event.target.value) || 0; recalc()"
                                        class="mx-auto w-20 text-center border border-gray-300 rounded-lg px-2 py-2 text-sm" />


                                </td>
                                {{-- Satuan --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="relative">
                                        <select x-model.number="item.satuan_id"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm appearance-none">
                                            <template x-for="s in item.satuans" :key="s.id">
                                                <option :value="s.id" :selected="item.satuan_id === s.id"
                                                    x-text="s.nama_satuan"></option>
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
                                            class="pl-10 pr-3 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-200" />
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
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">
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
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100">Kembali</a>
                    <button @click="update" type="button" :disabled="!canSubmit()"
                        class="px-4 py-2 rounded-lg w-full cursor-pointer"
                        :class="canSubmit() ?
                            'bg-[#344579] text-white hover:bg-[#2d3e6f]' :
                            'bg-slate-300 text-white cursor-not-allowed'">
                        Simpan Perubahan
                    </button>

                </div>


            </div>
        </div>
    </div>

    <script>
        function pembelianEditPage() {
            return {
                supplierQuery: {{ Js::from($pembelian->supplier->nama_supplier ?? '') }},
                supplierResults: [],
                subTotal: {{ $pembelian->sub_total ?? 0 }},
                totalPembayaran: {{ $pembelian->total ?? 0 }},
                originalForm: {},

                form: {
                    supplier_id: {{ (int) $pembelian->supplier_id }},
                    no_faktur: {{ Js::from($pembelian->no_faktur) }},
                    tanggal: {{ Js::from($pembelian->tanggal->format('Y-m-d')) }},
                    lunas: {{ Js::from($pembelian->status === 'paid') }},
                    deskripsi: {{ Js::from($pembelian->deskripsi) }},
                    biaya_transport: {{ (int) $pembelian->biaya_transport }},
                    items: [
                        @foreach ($pembelian->items as $it)
                            {
                                item_id: {{ (int) $it->item_id }},
                                query: {{ Js::from($it->item->nama_item) }},
                                results: [],
                                gudang_id: {{ (int) $it->gudang_id }},
                                satuan_id: {{ (int) $it->satuan_id }}, // ✅ integer
                                satuans: {!! $it->item->satuans->map(
                                    fn($s) => [
                                        'id' => (int) $s->id,
                                        'nama_satuan' => $s->nama_satuan,
                                    ],
                                ) !!},
                                jumlah: {{ (float) $it->jumlah }},
                                harga: {{ (int) $it->harga_beli }}
                            },
                        @endforeach
                    ]
                },

                init() {
                    this.originalForm = JSON.parse(JSON.stringify(this.form));
                    console.log("Init Items:", JSON.parse(JSON.stringify(this.form.items)));
                },


                // === VALIDASI & CEK PERUBAHAN ===
                isFormValid() {
                    if (!this.form.supplier_id || !this.form.tanggal) return false;
                    if (!this.form.items.length) return false;

                    for (let i of this.form.items) {
                        if (!i.item_id || !i.gudang_id || !i.satuan_id || !i.jumlah || !i.harga) {
                            return false;
                        }
                    }
                    return true;
                },

                isFormChanged() {
                    return JSON.stringify(this.form) !== JSON.stringify(this.originalForm);

                    console.log("Init items:", this.form.items);
                },

                canSubmit() {
                    return this.isFormValid() && this.isFormChanged();
                },

                // === SUPPLIER ===
                async searchSupplier() {
                    if (this.supplierQuery.length < 2) {
                        this.supplierResults = [];
                        return;
                    }
                    const res = await fetch(`/supplier/search?q=${encodeURIComponent(this.supplierQuery)}`);
                    this.supplierResults = await res.json();
                },

                selectSupplier(s) {
                    this.form.supplier_id = s.id;
                    this.supplierQuery = s.nama_supplier;
                    this.supplierResults = [];
                },

                // === ITEM ===
                async searchItem(idx) {
                    const q = this.form.items[idx].query;
                    if (!q || q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }
                    const res = await fetch(`{{ route('items.search') }}?q=${encodeURIComponent(q)}`);
                    const data = await res.json();
                    this.form.items[idx].results = data;
                },

                selectItem(idx, item) {
                    this.form.items[idx].item_id = item.id;
                    this.form.items[idx].query = item.nama_item;
                    this.form.items[idx].results = [];

                    // mapping satuan jadi integer
                    this.form.items[idx].satuans = (item.satuans || []).map(s => ({
                        id: Number(s.id),
                        nama_satuan: s.nama_satuan
                    }));

                    // ❌ jangan override kalau sudah punya satuan_id
                    if (!this.form.items[idx].satuan_id) {
                        if (item.satuan_default) {
                            this.form.items[idx].satuan_id = Number(item.satuan_default);
                        } else if (item.satuans && item.satuans.length > 0) {
                            this.form.items[idx].satuan_id = Number(item.satuans[0].id);
                        }
                    }

                    // 🔍 Debug cek apakah udah bener
                    console.log("SelectItem:", this.form.items[idx].satuan_id, this.form.items[idx].satuans);
                },


                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '{{ $gudangs->first()->id ?? '' }}',
                        satuan_id: '',
                        satuans: [],
                        jumlah: 1,
                        harga: 0
                    });
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                // === PERHITUNGAN ===
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
                    this.subTotal = this.form.items.reduce((sum, i) =>
                        sum + (parseFloat(i.jumlah) * parseInt(i.harga)), 0
                    );
                    this.totalPembayaran = this.subTotal + (parseInt(this.form.biaya_transport) || 0);
                },

                // === FORMATTER ===
                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0
                    }).format(n || 0);
                },

                formatJumlah(val) {
                    const num = parseFloat(val);
                    if (Number.isNaN(num)) return 0;
                    if (Number.isInteger(num)) return num;
                    return num;
                },

                // === SUBMIT ===
                async update() {
                    if (!this.canSubmit()) return;

                    const payload = {
                        ...this.form,
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        status: this.form.lunas ? 'paid' : 'unpaid'
                    };
                    delete payload.lunas;

                    payload.items = this.form.items.map(i => ({
                        item_id: i.item_id,
                        gudang_id: i.gudang_id,
                        satuan_id: i.satuan_id,
                        jumlah: parseFloat(i.jumlah) || 0,
                        harga: parseInt(i.harga) || 0
                    }));

                    const res = await fetch(`{{ route('pembelian.update', $pembelian->id) }}`, {
                        method: 'PUT',
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
                        console.error(await res.text());
                        alert("Gagal menyimpan perubahan");
                    }
                }
            }
        }
    </script>

@endsection
