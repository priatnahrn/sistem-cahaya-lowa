@extends('layouts.app')

@section('title', 'Detail Mutasi Stok')

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

    <div x-data="mutasiShowPage()" x-init="init()" class="space-y-6">
        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('mutasi-stok.index') }}" class="text-slate-500 hover:underline text-sm">Mutasi Stok</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Detail Mutasi Stok
                </span>
            </div>
        </div>

        {{-- INFORMASI UMUM (readonly) --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4 opacity-75 pointer-events-none">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Nomor Mutasi</label>
                    <input type="text" x-model="form.no_mutasi" readonly
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="form.tanggal" readonly
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 bg-slate-50 text-slate-700">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Gudang Asal</label>
                    <select x-model="form.gudang_asal_id" disabled
                        class="w-full px-3 py-2 pr-10 rounded-lg border border-gray-300 bg-slate-50 text-slate-700 appearance-none">
                        <option value="">-- Pilih Gudang Asal --</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-slate-700 mb-2">Gudang Tujuan</label>
                    <select x-model="form.gudang_tujuan_id" disabled
                        class="w-full px-3 py-2 pr-10 rounded-lg border border-gray-300 bg-slate-50 text-slate-700 appearance-none">
                        <option value="">-- Pilih Gudang Tujuan --</option>
                        @foreach ($gudangs as $g)
                            <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                        @endforeach
                    </select>
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
                            <th class="px-4 py-3 w-32 text-center">Stok</th> {{-- 🔹 tambahkan --}}
                            <th class="px-4 py-3 w-32 text-center">Jumlah</th>
                            <th class="px-4 py-3 w-32 text-center">Satuan</th>
                            <th class="px-2 py-3 w-12"></th>
                        </tr>
                    </thead>

                    <tbody>
                        <template x-for="(row, idx) in form.items" :key="idx">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                <td class="px-4 py-3 text-center" x-text="idx + 1"></td>
                                <td class="px-4 py-3">
                                    {{-- Input search item --}}
                                    <div class="relative" x-data="{ open: false }">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="row.query"
                                            @input.debounce.300ms="searchItem(idx); open = true" @focus="open = true"
                                            @click.away="open = false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-8 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                                                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">

                                        {{-- Dropdown hasil pencarian --}}
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
                                <td class="px-4 py-3 text-center" x-text="formatStok(row.stok)"></td>


                                {{-- Jumlah --}}
                                <td class="px-4 py-3 text-center">
                                    <input type="number" min="0" step="0.01" x-model.number="row.jumlah"
                                        class="mx-auto w-20 text-center border border-gray-300 rounded-lg px-2 py-2 text-sm" />
                                </td>

                                {{-- Satuan --}}
                                <td class="px-4 py-3 text-center">
                                    <select x-model="row.satuan_id" @change="updateStokFromCache(idx)"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm appearance-none">
                                        <template x-for="s in row.satuans" :key="s.id">
                                            <option :value="s.id" x-text="s.nama_satuan"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- Hapus --}}
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

            {{-- Tombol Tambah Item --}}
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
                Kembali
            </a>
            <button @click="update" type="button" :disabled="!isChanged" class="px-4 py-2 rounded-lg text-white"
                :class="isChanged ? 'bg-[#344579] hover:bg-[#2d3e6f]' : 'bg-gray-300 cursor-not-allowed'">
                Simpan Perubahan
            </button>
        </div>
    </div>



    <script>
        function mutasiShowPage() {
            return {
                toasts: [],
                allItems: @json($itemsArray),
                form: {
                    no_mutasi: @json($mutasiForJs['no_mutasi']),
                    tanggal: @json($mutasiForJs['tanggal_mutasi']),
                    gudang_asal_id: @json($mutasiForJs['gudang_asal_id']),
                    gudang_tujuan_id: @json($mutasiForJs['gudang_tujuan_id']),
                    items: @json($mutasiForJs['items'])
                },
                originalState: '',
                isChanged: false,

                init() {
                    this.originalState = JSON.stringify(this.form);

                    // Jalankan pengecekan stok langsung untuk semua item di awal
                    this.form.items.forEach((_, idx) => {
                        this.updateStokFromCache(idx);
                    });

                    // Pantau perubahan form
                    this.$watch('form', () => {
                        this.isChanged = JSON.stringify(this.form) !== this.originalState;
                    }, {
                        deep: true
                    });
                },


                addItem() {
                    this.form.items.push({
                        item_id: '',
                        query: '',
                        results: [],
                        satuans: [],
                        satuan_id: '',
                        jumlah: ''
                    });
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                },

                searchItem(idx) {
                    const q = this.form.items[idx].query?.toLowerCase().trim();
                    if (!q || q.length < 2) return;
                    this.form.items[idx].results = this.allItems.filter(i =>
                        i.nama_item.toLowerCase().includes(q) ||
                        i.kode_item.toLowerCase().includes(q)
                    ).slice(0, 15);
                },

                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.satuans = item.satuans || [];
                    if (row.satuans.length > 0) {
                        row.satuan_id = row.satuans[0].id;
                    }
                    this.updateStokFromCache(idx);

                },

                async update() {
                    if (!this.isChanged) return;

                    try {
                        const res = await fetch(`{{ route('mutasi-stok.update', $mutasi->id) }}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(this.form)
                        });

                        if (res.ok) {
                            this.toasts.push({
                                type: 'success',
                                message: 'Perubahan berhasil disimpan.'
                            });
                            this.originalState = JSON.stringify(this.form);
                            this.isChanged = false;
                        } else {
                            const err = await res.json().catch(() => null);
                            (err?.errors ? Object.values(err.errors).flat() : ['Gagal menyimpan perubahan'])
                            .forEach(msg => this.toasts.push({
                                type: 'error',
                                message: msg
                            }));
                        }
                    } catch (e) {
                        console.error(e);
                        this.toasts.push({
                            type: 'error',
                            message: 'Terjadi kesalahan koneksi ke server.'
                        });
                    }
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

                    row.stok = stokData ? parseFloat(stokData.stok) : 0;
                },
                formatStok(n) {
                    if (n === null || n === '-' || n === undefined || isNaN(n)) return '-';
                    const num = parseFloat(n);
                    return Number.isInteger(num) ?
                        num.toString() :
                        num.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                },

            }
        }
    </script>
@endsection
