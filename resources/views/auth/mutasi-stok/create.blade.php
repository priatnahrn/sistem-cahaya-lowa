@extends('layouts.app')

@section('title', 'Tambah Mutasi Stok')

@section('content')
    {{-- Toast Container --}}
    <div x-data="{ toasts: [] }" x-init="$watch('toasts', () => { setTimeout(() => toasts.shift(), 4000) })" class="fixed top-6 right-6 space-y-3 z-50 w-80">
        <template x-for="(t, i) in toasts" :key="i">
            <div x-transition class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                :class="t.type === 'error' ?
                    'bg-rose-50 text-rose-700 border border-rose-200' :
                    'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

    <div x-data="mutasiCreatePage()" x-init="init()" class="space-y-6">
        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('mutasi-stok.index') }}" class="text-slate-500 hover:underline text-sm">Mutasi Stok</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Mutasi Stok
                </span>
            </div>
        </div>

        {{-- INFORMASI UMUM --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Nomor Mutasi --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Nomor Mutasi</label>
                    <input type="text" x-model="form.no_mutasi" readonly
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-slate-50 text-slate-700">
                </div>

                {{-- Tanggal --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="form.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-slate-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Gudang Asal --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Gudang Asal</label>
                    <div class="relative">
                        <select x-model="form.gudang_asal_id" @change="updateAllStok()"
                            class="w-full px-3 py-2 pr-10 rounded-lg border border-gray-300 text-slate-700 appearance-none">
                            <option value="">-- Pilih Gudang Asal --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>

                {{-- Gudang Tujuan --}}
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Gudang Tujuan</label>
                    <div class="relative">
                        <select x-model="form.gudang_tujuan_id"
                            class="w-full px-3 py-2 pr-10 rounded-lg border border-gray-300 text-slate-700 appearance-none">
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            @foreach ($gudangs as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABEL ITEM --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 w-32 text-center">Stok</th>
                            <th class="px-4 py-3 w-32 text-center">Jumlah</th>
                            <th class="px-4 py-3 w-32 text-center">Satuan</th>
                            <th class="px-2 py-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, idx) in form.items" :key="idx">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                <td class="px-4 py-3 text-center" x-text="idx + 1"></td>

                                {{-- ITEM --}}
                                <td class="px-4 py-3">
                                    <div class="relative" x-data="{ open: false }">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="row.query"
                                            @input.debounce.300ms="searchItem(idx); open = true" @focus="open = true"
                                            @click.away="open = false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-8 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                                                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>

                                        {{-- Dropdown --}}
                                        <div x-show="open && row.query.length >= 2 && !row.item_id" x-cloak x-transition
                                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <div class="p-2">
                                                <div x-show="row.results.length === 0"
                                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                    Tidak ada item ditemukan
                                                </div>

                                                <template x-for="r in row.results" :key="r.id">
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

                                {{-- STOK --}}
                                <td class="px-4 py-3 text-center" x-text="formatStok(row.stok)"></td>

                                {{-- JUMLAH --}}
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="0" step="0.01" x-model.number="row.jumlah"
                                        class="mx-auto w-20 text-center border border-gray-300 rounded-lg px-2 py-2 text-sm" />
                                </td>

                                {{-- SATUAN --}}
                                <td class="px-4 py-3 text-center">
                                    <div class="relative">
                                        <select x-model="row.satuan_id" @change="updateStokFromCache(idx)"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm appearance-none">
                                            <option value="">Pilih</option>
                                            <template x-for="s in row.satuans" :key="s.id">
                                                <option :value="s.id" x-text="s.nama_satuan"></option>
                                            </template>
                                        </select>
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    </div>
                                </td>

                                {{-- HAPUS --}}
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

        {{-- ACTION BUTTONS --}}
        <div class="flex justify-end gap-3 pt-4">
            <a href="{{ route('mutasi-stok.index') }}"
                class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                Batal
            </a>
            <button @click="save" type="button" :disabled="!isValid()" class="px-4 py-2 rounded-lg text-white"
                :class="isValid() ? 'bg-[#344579] hover:bg-[#2d3e6f]' : 'bg-gray-300 cursor-not-allowed'">
                Simpan
            </button>
        </div>
    </div>

    @php
        $itemsJson = $items
            ->map(
                fn($i) => [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'satuans' => $i->satuans->map(
                        fn($s) => [
                            'id' => $s->id,
                            'nama_satuan' => $s->nama_satuan,
                        ],
                    ),
                    // stok_data sudah array dari controller, jadi langsung ambil aja
                    'stok_data' => $i->stok_data,
                ],
            )
            ->toArray();
    @endphp


    <script>
        function mutasiCreatePage() {
            return {
                toasts: [],
                allItems: @json($itemsJson),
                form: {
                    no_mutasi: @json($newCode),
                    tanggal: new Date().toISOString().split('T')[0],
                    gudang_asal_id: '',
                    gudang_tujuan_id: '',
                    items: []
                },

                init() {
                    this.addItem();
                    console.log('%c[INIT]', 'color: gray;', 'Mutasi page loaded');
                },

                addItem() {
                    this.form.items.push({
                        item_id: '',
                        query: '',
                        results: [],
                        stok: null,
                        satuan_id: '',
                        satuans: [],
                        jumlah: ''
                    });
                    console.log('%c[ITEM]', 'color: orange;', 'Item row ditambahkan', this.form.items);
                },

                removeItem(idx) {
                    console.log('%c[REMOVE]', 'color: red;', 'Menghapus item index:', idx);
                    this.form.items.splice(idx, 1);
                },

                searchItem(idx) {
                    const q = this.form.items[idx].query.toLowerCase();
                    if (q.length < 2) {
                        this.form.items[idx].results = [];
                        return;
                    }

                    this.form.items[idx].results = this.allItems.filter(i =>
                        i.nama_item.toLowerCase().includes(q) || i.kode_item.toLowerCase().includes(q)
                    ).slice(0, 15);

                    console.log('%c[SEARCH ITEM]', 'color: #007acc;', 'Query:', q,
                        '\nHasil:', this.form.items[idx].results);
                },

                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.satuans = item.satuans || [];

                    if (row.satuans.length > 0) {
                        row.satuan_id = row.satuans[0].id;
                    }

                    console.log('%c[SELECT ITEM]', 'color: #4CAF50;', {
                        itemTerpilih: item.nama_item,
                        satuans: row.satuans,
                        stokData: item.stok_data
                    });

                    this.updateStokFromCache(idx);
                },

                updateAllStok() {
                    console.log('%c[GUDANG ASAL DIPILIH]', 'color: #FF9800;',
                        'Gudang Asal ID:', this.form.gudang_asal_id);
                    this.form.items.forEach((_, idx) => this.updateStokFromCache(idx));
                },

                formatStok(n) {
                    if (n == null || isNaN(n)) return '-';
                    const num = parseFloat(n);
                    return Number.isInteger(num) ?
                        num.toString() :
                        num.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                },

                updateStokFromCache(idx) {
                    const row = this.form.items[idx];
                    if (!this.form.gudang_asal_id || !row.item_id || !row.satuan_id) {
                        row.stok = '-';
                        return;
                    }

                    const item = this.allItems.find(i => i.id == row.item_id);
                    if (!item || !item.stok_data) {
                        row.stok = '-';
                        return;
                    }

                    const stokData = item.stok_data.find(
                        s => s.gudang_id == this.form.gudang_asal_id && s.satuan_id == row.satuan_id
                    );

                    row.stok = this.formatStok(stokData ? stokData.stok : 0);

                    console.log('%c[UPDATE STOK]', 'color: #9C27B0;', {
                        item: row.query,
                        gudang: this.form.gudang_asal_id,
                        satuan: row.satuan_id,
                        stokTersedia: row.stok
                    });
                },

                isValid() {
                    if (!this.form.no_mutasi || !this.form.tanggal || !this.form.gudang_asal_id ||
                        !this.form.gudang_tujuan_id)
                        return false;
                    if (this.form.gudang_asal_id === this.form.gudang_tujuan_id) return false;
                    if (this.form.items.length === 0) return false;
                    for (let i of this.form.items) {
                        if (!i.item_id || !i.satuan_id || !i.jumlah || i.jumlah <= 0) return false;
                    }
                    return true;
                },

                async save() {
                    if (!this.isValid()) return;
                    const payload = JSON.stringify(this.form);

                    console.log('%c[SAVE]', 'color: #2196F3;', 'Payload dikirim:', this.form);

                    const res = await fetch(`{{ route('mutasi-stok.store') }}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: payload
                    });

                    if (res.ok) {
                        window.location.href = "{{ route('mutasi-stok.index') }}";
                    } else {
                        const err = await res.json().catch(() => null);
                        (err?.errors ? Object.values(err.errors).flat() : ['Gagal menyimpan mutasi stok']).forEach(
                            msg => {
                                this.toasts.push({
                                    type: 'error',
                                    message: msg
                                });
                            });
                    }
                }
            };
        }
    </script>

@endsection
