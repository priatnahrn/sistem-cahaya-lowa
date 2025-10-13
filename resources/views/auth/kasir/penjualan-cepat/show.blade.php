<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Penjualan Cepat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="//unpkg.com/alpinejs" defer></script>
    <style>
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

<body class="bg-slate-100 text-slate-700">

    <div x-data="editPenjualanCepat()" x-init="init()" class="min-h-screen bg-slate-100">
        <!-- HEADER -->
        <header class="bg-[#344579] text-white py-4 px-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan-cepat.index') }}"
                    class="bg-[#2c3e6b] hover:bg-[#24355b] px-3 py-2 rounded-md flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <h1 class="font-semibold text-lg">Edit Penjualan Cepat</h1>
            </div>
            <div class="text-right text-sm">
                <div>No. Faktur: <strong x-text="form.no_faktur"></strong></div>
                <div x-text="fmtDate(form.tanggal)"></div>
            </div>
        </header>

        <!-- MAIN -->
        <main class="max-w-[95%] mx-auto mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
            <!-- LEFT -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-700 mb-4">Daftar Item</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-left text-slate-600">
                                <th class="px-3 py-3 text-center w-10">No</th>
                                <th class="px-3 py-3">Nama Item</th>
                                <th class="px-3 py-3 text-center w-40">Gudang</th>
                                <th class="px-3 py-3 text-center w-32">Satuan</th>
                                <th class="px-3 py-3 text-center w-24">Stok</th>
                                <th class="px-3 py-3 text-center w-24">Jumlah</th>
                                <th class="px-3 py-3 text-center w-36">Harga</th>
                                <th class="px-3 py-3 text-center w-36">Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(it, idx) in form.items" :key="idx">
                                <tr class="border-b hover:bg-slate-50 text-slate-700">
                                    <td class="text-center px-3 py-2" x-text="idx + 1"></td>

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
                                                class="absolute z-30 bg-white border border-slate-200 rounded-lg w-full mt-1 max-h-56 overflow-auto text-sm">
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
                                    </td>

                                    <!-- Satuan -->
                                    <td class="px-3 py-2 text-center">
                                        <div class="relative">
                                            <select x-model="it.satuan_id" @change="updateStockAndPrice(idx)"
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

                                    <!-- Stok -->
                                    <td class="px-3 py-2 text-center">
                                        <div class="text-xs"
                                            :class="getStockForSelected(it) > 0 ? 'text-slate-500' : 'text-rose-600 font-semibold'">
                                            <span x-text="formatStok(getStockForSelected(it))"></span>
                                        </div>
                                    </td>

                                    <!-- Qty -->
                                    <td class="px-3 py-2 text-center">
                                        <input type="number" min="1" x-model.number="it.qty" @input="recalc()"
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
                                        <button @click="removeItem(idx)" type="button"
                                            class="text-rose-600 hover:text-rose-800">
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
                    <button @click="addItem()" type="button"
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

                <div class="mt-6">
                    <button @click="update()" type="button"
                        class="w-full bg-[#344579] hover:bg-[#2d3f6b] text-white py-2.5 rounded-md text-sm font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </main>
    </div>

    @php
        $itemsJson = \App\Models\Item::with(['gudangItems.gudang', 'gudangItems.satuan'])
            ->get()
            ->map(function($i) {
                return [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'gudangs' => $i->gudangItems->map(function($ig) {
                        return [
                            'gudang_id' => $ig->gudang?->id,
                            'nama_gudang' => $ig->gudang?->nama_gudang,
                            'satuan_id' => $ig->satuan?->id,
                            'nama_satuan' => $ig->satuan?->nama_satuan,
                            'stok' => $ig->stok,
                            'harga_retail' => $ig->satuan?->harga_retail ?? 0,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();

        // Prepare existing items data
        $existingItemsJson = $penjualan->items->map(function($it) {
            return [
                'item_id' => $it->item_id,
                'nama_item' => $it->item->nama_item,
                'gudang_id' => $it->gudang_id,
                'satuan_id' => $it->satuan_id,
                'qty' => $it->jumlah,
                'harga' => $it->harga,
                'gudangs' => $it->item->gudangItems->map(function($ig) {
                    return [
                        'gudang_id' => $ig->gudang_id,
                        'nama_gudang' => $ig->gudang->nama_gudang ?? '',
                        'satuan_id' => $ig->satuan_id,
                        'nama_satuan' => $ig->satuan->nama_satuan ?? '',
                        'stok' => $ig->stok ?? 0,
                        'harga_retail' => $ig->satuan->harga_retail ?? 0,
                    ];
                })->toArray()
            ];
        })->toArray();
    @endphp

    <script>
        function editPenjualanCepat() {
            return {
                form: {
                    no_faktur: @json($penjualan->no_faktur),
                    tanggal: @json($penjualan->tanggal->format('Y-m-d')),
                    items: []
                },
                allItems: @json($itemsJson),
                existingItems: @json($existingItemsJson),
                subtotal: 0,
                total: 0,

                init() {
                    // Load existing items dari variable yang sudah di-prepare di PHP
                    this.existingItems.forEach(item => {
                        this.form.items.push({
                            item_id: item.item_id,
                            query: item.nama_item,
                            results: [],
                            gudang_id: item.gudang_id,
                            satuan_id: item.satuan_id,
                            qty: item.qty,
                            harga: item.harga,
                            gudangs: item.gudangs,
                            filteredSatuans: [],
                            stok: 0
                        });
                    });

                    // Update satuan options dan hitung total
                    this.$nextTick(() => {
                        this.form.items.forEach((item, idx) => {
                            this.updateSatuanOptions(idx);
                        });
                        this.recalc();
                    });
                },

                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '',
                        gudangs: [],
                        satuan_id: '',
                        filteredSatuans: [],
                        qty: 1,
                        harga: 0,
                        stok: 0
                    });
                },

                removeItem(idx) {
                    if (confirm('Hapus item ini?')) {
                        this.form.items.splice(idx, 1);
                        this.recalc();
                    }
                },

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

                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.gudangs = item.gudangs || [];

                    // Reset untuk item baru
                    row.gudang_id = '';
                    row.satuan_id = '';
                    row.filteredSatuans = [];
                    row.qty = 1;
                    row.harga = 0;
                    row.stok = 0;

                    if (row.gudangs.length > 0) {
                        row.gudang_id = row.gudangs[0].gudang_id;
                        this.updateSatuanOptions(idx);
                    }
                },

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
                        // Preserve existing satuan_id jika valid
                        const currentSatuanValid = item.satuan_id && 
                            item.filteredSatuans.find(s => s.satuan_id == item.satuan_id);
                        
                        if (!currentSatuanValid) {
                            item.satuan_id = item.filteredSatuans[0].satuan_id;
                        }
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        item.harga = 0;
                    }
                },

                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(
                        g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id
                    );

                    if (selected) {
                        item.stok = selected.stok || 0;
                        // Preserve existing harga, hanya set default untuk item baru
                        if (!item.harga || item.harga === 0) {
                            item.harga = parseFloat(selected.harga_retail || 0);
                        }
                    } else {
                        item.stok = 0;
                        if (!item.harga) {
                            item.harga = 0;
                        }
                    }
                    this.recalc();
                },

                getStockForSelected(it) {
                    if (!it.gudang_id || !it.satuan_id) return 0;
                    const found = it.gudangs.find(
                        g => g.gudang_id == it.gudang_id && g.satuan_id == it.satuan_id
                    );
                    return found ? found.stok || 0 : 0;
                },

                recalc() {
                    this.subtotal = this.form.items.reduce(
                        (sum, it) => sum + ((+it.qty || 0) * (+it.harga || 0)),
                        0
                    );
                    this.total = this.subtotal;
                },

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

                async update() {
                    if (!this.form.items.length) {
                        alert('Minimal harus ada 1 item');
                        return;
                    }

                    const payload = {
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
                    };

                    try {
                        const res = await fetch('{{ route('penjualan-cepat.update', $penjualan->id) }}', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();

                        if (data.success) {
                            alert('Perubahan berhasil disimpan!');
                            window.location.href = '{{ route('penjualan-cepat.index') }}';
                        } else {
                            alert(data.message || 'Gagal menyimpan perubahan');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Terjadi kesalahan koneksi');
                    }
                }
            };
        }
    </script>

</body>

</html>