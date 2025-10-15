@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <div x-data="pembelianEditPage()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('pembelian.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- 📦 INFO UMUM --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Supplier --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <div class="relative" @click.away="supplierResults = []">
                        <input type="text" x-model="supplierQuery"
                            @input.debounce.300ms="
                                if (supplierQuery.length >= 2) {
                                    searchSupplier();
                                } else {
                                    supplierResults = [];
                                }
                            "
                            placeholder="Cari supplier"
                            class="w-full pl-4 pr-12 py-2.5 rounded-lg border border-slate-300
                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                        {{-- Icon pencarian --}}
                        <span x-show="!form.supplier_id" x-cloak x-transition.opacity.duration.150ms
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>

                        <input type="hidden" name="supplier_id" :value="form.supplier_id">

                        {{-- Dropdown hasil pencarian --}}
                        <ul x-show="supplierResults.length" x-cloak
                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg text-sm max-h-56 overflow-auto">
                            <template x-for="s in supplierResults" :key="s.id">
                                <li @click="selectSupplier(s); supplierResults = []"
                                    class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition border-b border-slate-100 last:border-b-0">
                                    <div class="font-medium text-slate-800" x-text="s.nama_supplier"></div>
                                    <small class="text-slate-500" x-text="s.kontak || '-'"></small>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>

                {{-- Checkbox Lunas --}}
                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" id="lunas" x-model="form.lunas"
                        class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-2 focus:ring-blue-200">
                    <label for="lunas" class="text-sm font-medium text-slate-700 cursor-pointer">Lunas</label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Nomor Faktur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Faktur</label>
                    <input type="text" x-model="form.no_faktur" readonly
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-600">
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="form.tanggal"
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-slate-700
                            focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi</label>
                    <input type="text" x-model="form.deskripsi" placeholder="Opsional"
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 text-slate-700
                            focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>
        </div>

        {{-- 📋 DAFTAR ITEM --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            {{-- Header --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800">Daftar Item Pembelian</h3>
            </div>

            {{-- Isi tabel --}}
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
                                            @input.debounce.300ms="
                                                if (item.query.length >= 2) {
                                                    searchItem(idx);
                                                } else {
                                                    item.results = [];
                                                }
                                            "
                                            @focus="item._dropdownOpen = (item.query && item.query.length >= 2)"
                                            @click="item._dropdownOpen = (item.query && item.query.length >= 2)"
                                            @keydown.escape="item._dropdownOpen = false" placeholder="Cari item"
                                            class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm 
                                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                        {{-- Icon pencarian --}}
                                        <span x-show="!item.item_id" x-cloak x-transition.opacity.duration.150ms
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        {{-- Dropdown hasil pencarian --}}
                                        <ul x-show="item._dropdownOpen && item.query.length >= 2 && !item.item_id" x-cloak
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto text-sm">
                                            <div x-show="item.results.length === 0"
                                                class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                Tidak ada item ditemukan
                                            </div>
                                            <template x-for="r in item.results" :key="r.id">
                                                <li @click="selectItem(idx, r); item._dropdownOpen = false;"
                                                    class="px-3 py-2 cursor-pointer hover:bg-slate-50 transition border-b border-slate-100 last:border-b-0">
                                                    <div class="font-medium text-slate-800" x-text="r.nama_item"></div>
                                                    <small class="text-slate-500" x-text="r.kode_item"></small>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </td>

                                {{-- Gudang --}}
                                <td class="px-5 py-4 text-center align-middle">
                                    <div class="relative">
                                        <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 pr-8 text-sm appearance-none
                                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
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
                                <td class="px-5 py-4 text-center align-middle">
                                    <input type="text" :value="formatJumlah(item.jumlah)"
                                        @input="item.jumlah = parseFloat($event.target.value.replace(/\./g, '').replace(',', '.')) || 0; recalc()"
                                        class="no-spinner w-24 text-center border border-slate-300 rounded-lg px-2 py-2.5 
                                            focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        inputmode="numeric" />
                                </td>

                                {{-- Satuan --}}
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <select x-model.number="item.satuan_id"
                                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 pr-8 text-sm appearance-none
                                                focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
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
                                <td class="px-5 py-4 text-right align-middle">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateHarga(idx, $event.target.value)"
                                            class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg py-2.5 
                                                focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                    </div>
                                </td>

                                {{-- Total --}}
                                <td
                                    class="px-5 py-4 text-right font-semibold text-slate-800 align-middle whitespace-nowrap">
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

        {{-- 💰 RINGKASAN --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="text-slate-600 text-sm mb-1 block">Biaya Transportasi</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="text" :value="formatRupiah(form.biaya_transport)"
                            @input="updateTransport($event.target.value)" placeholder="0"
                            class="pl-10 pr-3 w-full border border-slate-300 rounded-lg px-3 py-2.5 text-right 
                                focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-4 mt-4"></div>

                <div class="flex justify-between items-center mb-6">
                    <div class="text-slate-700 font-bold text-lg">TOTAL</div>
                    <div class="text-[#344579] text-2xl font-extrabold tracking-wide">
                        Rp <span x-text="formatRupiah(totalPembayaran)"></span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 w-full">
                    <a href="{{ route('pembelian.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition">
                        Kembali
                    </a>
                    <button @click="update" type="button" :disabled="!canSubmit()"
                        :class="[
                            'px-5 py-2.5 rounded-lg text-white font-medium transition w-full',
                            canSubmit() ?
                            'bg-[#334579] hover:bg-[#2d3e6f] cursor-pointer' :
                            'bg-slate-300 cursor-not-allowed'
                        ]">
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
                                harga: {{ (int) $it->harga_beli }},
                                 _dropdownOpen: false,
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
        this.form.items[idx]._dropdownOpen = false; // ✅ CLOSE jika < 2 char
        return;
    }
    const res = await fetch(`{{ route('items.search') }}?q=${encodeURIComponent(q)}`);
    const data = await res.json();
    this.form.items[idx].results = data;
    this.form.items[idx]._dropdownOpen = true; // ✅ OPEN saat ada hasil
},

                selectItem(idx, item) {
                    this.form.items[idx].item_id = item.id;
                    this.form.items[idx].query = item.nama_item;
                    this.form.items[idx].results = [];
                    this.form.items[idx]._dropdownOpen = false;

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
                        harga: 0,
                         _dropdownOpen: false,
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
