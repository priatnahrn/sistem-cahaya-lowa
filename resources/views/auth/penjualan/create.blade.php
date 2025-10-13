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

    {{-- 🌟 Root Alpine Component --}}
    <div x-data="penjualanCreatePage()" x-init="init();
    $watch('form.mode', () => watchMode())" data-no-faktur="{{ $noFakturPreview }}"
        data-tanggal="{{ now()->toDateString() }}" class="space-y-6">

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

        {{-- 🧭 Breadcrumb Navigasi --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                Tambah Penjualan Baru
            </span>
        </div>

        {{-- 🎯 Hidden Input untuk Scanner Barcode --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        {{-- 📦 Card Utama --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">

                {{-- 🧍 Input Pelanggan --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                        <div class="relative" @click.away="handlePelangganClickAway()">
                            {{-- Input pelanggan --}}
                            <input type="text" x-model="pelangganQuery"
                                @input.debounce.300ms="
                    if (pelangganQuery.length >= 2) {
                        searchPelanggan();
                        openResults = true;
                    } else {
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
                                @focus="openResults = (pelangganQuery.length >= 2)" placeholder="Cari pelanggan"
                                class="w-full pl-4 pr-12 py-2.5 rounded-lg border border-slate-300
                    focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                            {{-- Icon pencarian di kanan (NON-CLICKABLE). Akan DISAPPEAR saat form.pelanggan_id truthy --}}
                            <span x-show="!form.pelanggan_id" x-cloak x-transition.opacity.duration.150ms
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            {{-- Badge di dalam input (tetap muncul saat ada pelanggan atau 'Customer' default).
                 Posisi badge menyesuaikan: jika ada pelanggan (ikon hilang) badge pindah lebih ke kanan. --}}
                            <span x-show="form.pelanggan_id" x-cloak x-transition.opacity.duration.150ms
                                :class="[
                                    'absolute right-3 top-1/2 -translate-y-1/2 text-xs px-2 py-0.5 rounded font-medium select-none',
                                    selectedPelangganLevel === 'partai_kecil' ? 'bg-yellow-100 text-yellow-700' :
                                    selectedPelangganLevel === 'grosir' ? 'bg-green-100 text-green-700' :
                                    'bg-blue-100 text-blue-700'
                                ]"
                                x-text="formatLevel(selectedPelangganLevel || 'retail')">
                            </span>


                            {{-- Dropdown hasil pencarian --}}
                            <div x-show="openResults && pelangganQuery.length >= 2" x-cloak
                                class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200
                       rounded-lg shadow-lg text-sm max-h-56 overflow-auto">

                                <template x-if="pelangganLoading">
                                    <div class="px-4 py-3 text-gray-500 text-center">
                                        <i class="fa-solid fa-spinner fa-spin mr-2"></i> Mencari pelanggan...
                                    </div>
                                </template>

                                <template x-if="!pelangganLoading && pelangganResults.length > 0">
                                    <ul>
                                        <template x-for="p in pelangganResults" :key="p.id">
                                            <li @click="selectPelanggan(p); openResults = false"
                                                class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition border-b border-slate-100 last:border-b-0">
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

                                <template x-if="!pelangganLoading && pelangganResults.length === 0">
                                    <div class="px-4 py-3">
                                        <div class="text-gray-500 italic mb-3 text-center">
                                            <i class="fa-solid fa-user-slash mr-1"></i>
                                            Pelanggan "<span x-text="pelangganQuery"></span>" tidak ditemukan
                                        </div>
                                        <button type="button" @click="openTambahPelanggan(); openResults = false"
                                            class="w-full px-4 py-2 bg-[#334976] hover:bg-[#2d3d6d] text-white rounded-lg transition font-medium">
                                            <i class="fa-solid fa-user-plus mr-2"></i> Tambah Pelanggan Baru
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ⚙️ Mode, Faktur, Tanggal --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">No. Nota</label>
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
                        <label class="block text-sm font-medium text-slate-700 mb-2">Pengiriman</label>
                        <div class="relative">
                            <select name="mode" x-model="form.mode"
                                class="w-full px-3 py-2.5 rounded-lg border border-slate-200
                                           appearance-none pr-8 bg-white">
                                <option value="ambil">Ambil Sendiri</option>
                                <option value="antar">Butuh Pengiriman</option>
                            </select>
                            <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
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
                                <!-- Nomor urut -->
                                <td class="px-5 py-4 text-center font-medium align-middle" x-text="idx + 1"></td>

                                <!-- ===========================
                     Nama Item (ICON KANAN + HILANG SAAT ITEM DIPILIH)
                     ============================ -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative" x-data="{
                                        open: false,
                                        teleStyle() {
                                            try {
                                                const el = this.$refs.itemInput;
                                                if (!el) return 'position:absolute; display:none;';
                                                const rect = el.getBoundingClientRect();
                                                const top = rect.bottom + window.scrollY;
                                                const left = rect.left + window.scrollX;
                                                const width = rect.width;
                                                return `position:absolute; top:${top}px; left:${left}px; width:${width}px; z-index:9999;`;
                                            } catch (e) { return 'position:absolute; display:none;'; }
                                        }
                                    }" @keydown.escape="open = false"
                                        @resize.window="$nextTick(() => {})">

                                        <!-- input pencarian item -->
                                        <input type="text" x-ref="itemInput" x-model="item.query"
                                            @input.debounce.300ms="
                if (item.query.length >= 2) {
                    searchItem(idx);
                    open = true;
                } else {
                    item.item_id = null;
                    item.gudang_id = '';
                    item.satuan_id = '';
                    item.gudangs = [];
                    item.filteredSatuans = [];
                    item.stok = 0;
                    item.harga = 0;
                    item.results = [];
                    open = false;
                }"
                                            @focus="open = (item.query && item.query.length >= 2)"
                                            @click="open = (item.query && item.query.length >= 2)" placeholder="Cari item"
                                            class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm 
                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                        <!-- 🔍 ICON PENCARIAN KANAN -->
                                        <span x-show="!item.item_id" x-cloak x-transition.opacity.duration.150ms
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        <!-- ========== TELEPORT DROPDOWN (muncul di bawah input) ========== -->
                                        <template x-teleport="body">
                                            <div x-show="open && item.query.length >= 2 && !item.item_id" x-cloak
                                                x-transition:enter="transition ease-out duration-150"
                                                x-transition:enter-start="opacity-0 transform -translate-y-1"
                                                x-transition:enter-end="opacity-100 transform translate-y-0"
                                                x-transition:leave="transition ease-in duration-100"
                                                x-transition:leave-start="opacity-100 transform translate-y-0"
                                                x-transition:leave-end="opacity-0 transform -translate-y-1"
                                                :style="teleStyle()"
                                                class="bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto text-sm">

                                                <div class="p-2">
                                                    <div x-show="item.results.length === 0"
                                                        class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                                        Tidak ada item ditemukan
                                                    </div>

                                                    <template x-for="r in item.results" :key="r.id">
                                                        <div @click="
                            selectItem(idx, r);
                            open = false;
                        "
                                                            class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded">
                                                            <div class="font-medium" x-text="r.nama_item"></div>
                                                            <div class="text-xs text-slate-500" x-text="r.kode_item">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                        <!-- ========== END TELEPORT DROPDOWN ========== -->
                                    </div>
                                </td>


                                {{-- <!-- Keterangan (opsional) -->
                                <td class="px-5 py-4 align-middle">
                                    <input type="text" x-model="item.keterangan" placeholder="Catatan item (opsional)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700
                                    focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                </td> --}}

                                <!-- Gudang -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative w-full">
                                        <div
                                            class="border border-slate-300 rounded-lg px-3 pr-8 py-[6px] text-sm text-slate-700 
                focus-within:ring-2 focus-within:ring-[#344579]/20 focus-within:border-[#344579] transition">
                                            <div class="flex flex-col leading-tight">
                                                <!-- Nama gudang (tampil nama terpilih / opsi pertama / placeholder) -->
                                                <div class="text-[13px] text-slate-700">
                                                    <span
                                                        x-text="(getDistinctGudangs(item).find(g => g.gudang_id == item.gudang_id) || getDistinctGudangs(item)[0] || {}).nama_gudang || '-'">
                                                    </span>

                                                    <!-- select transparan tetap ada di atas teks untuk interaksi -->
                                                    <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                                        class="absolute inset-0 opacity-0 cursor-pointer">
                                                        <template x-for="g in getDistinctGudangs(item)"
                                                            :key="g.gudang_id">
                                                            <option :value="g.gudang_id" x-text="g.nama_gudang"></option>
                                                        </template>
                                                    </select>
                                                </div>

                                                <!-- Stok: selalu tampil label. Angka hanya kalau gudang dipilih.
                                                                                     Warna berubah merah kalau gudang dipilih dan stok === 0 -->
                                                <div
                                                    :class="(item.gudang_id && (parseFloat(item.stok) === 0)) ?
                                                    'text-rose-600 font-semibold text-[11px] mt-[1px]' :
                                                    'text-slate-500 text-[11px] mt-[1px]'">
                                                    Stok: <span
                                                        x-text="item.gudang_id ? formatStok(item.stok) : ''"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Ikon dropdown -->
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[12px]"></i>
                                    </div>
                                </td>

                                <!-- Jumlah -->
                                <td class="px-5 py-4 text-center align-middle">
                                    <input type="text" :value="item.jumlah ? formatJumlah(item.jumlah) : ''"
                                        @input="updateJumlahFormatted(idx, $event.target.value)"
                                        class="no-spinner w-24 text-center border border-slate-300 rounded-lg px-2 py-2.5 
           focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                        inputmode="numeric" pattern="[0-9]*" />
                                </td>

                                <!-- Satuan -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <select x-model="item.satuan_id" @change="updateStockAndPrice(idx)"
                                            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-slate-700 
                                        appearance-none focus:outline-none focus:ring-2 focus:ring-[#344579]/20 
                                        focus:border-[#344579] transition">
                                            <template x-for="s in item.filteredSatuans" :key="s.satuan_id">
                                                <option :value="s.satuan_id" x-text="s.nama_satuan"></option>
                                            </template>
                                        </select>
                                        <i
                                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                    </div>
                                </td>

                                <!-- Harga -->
                                <td class="px-5 py-4 text-right align-middle">
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                        <input type="text" :value="formatRupiah(item.harga)"
                                            @input="updateManualPrice(idx, $event.target.value)"
                                            class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg py-2.5 
                                        focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                    </div>
                                </td>

                                <!-- Total -->
                                <td
                                    class="px-5 py-4 text-right font-semibold text-slate-800 align-middle whitespace-nowrap">
                                    Rp <span x-text="formatRupiah(item.jumlah * item.harga)"></span>
                                </td>

                                <!-- Hapus -->
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
                    <button type="button" @click="addItemManual"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                        <i class="fa-solid fa-plus"></i> Tambah Item Baru
                    </button>
                </div>
            </div>
        </div>



        {{-- === RINGKASAN PENJUALAN === --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div :class="totalPembayaran > lebarThreshold ? 'w-full md:w-[40%]' : 'w-full md:w-96'"
                class="bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6 transition-all duration-300">

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
                    <button @click="saveDraft" type="button"
                        class="px-5 py-2.5 rounded-lg border border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-white transition cursor-pointer">
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
            class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 min-h-screen">

            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
                <h2 class="text-lg font-semibold mb-4">Tambah Pelanggan Baru</h2>

                {{-- Form isi data pelanggan --}}
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Pelanggan</label>
                        <input type="text" x-model="newPelanggan.nama_pelanggan" placeholder="Nama pelanggan"
                            class="w-full px-3 py-2 border rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                        <input type="text" x-model="newPelanggan.kontak" placeholder="Contoh: 08XXX / 62XXX"
                            class="w-full px-3 py-2 border rounded-lg border-slate-300">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Alamat</label>
                        <textarea x-model="newPelanggan.alamat" class="w-full px-3 py-2 border rounded-lg border-slate-300"
                            placeholder="Alamat pelanggan (opsional)"></textarea>
                    </div>
                    <label class="block text-sm text-slate-600 mb-1">Level</label>
                    <div class="relative">
                        <select x-model="newPelanggan.level"
                            class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-slate-700 
                                        appearance-none focus:outline-none focus:ring-2 focus:ring-[#344579]/20 
                                        focus:border-[#344579] transition">
                            <option value="retail">Retail</option>
                            <option value="partai_kecil">Partai Kecil</option>
                            <option value="grosir">Grosir</option>
                        </select>
                        <i
                            class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    </div>
                </div>

                {{-- Tombol Aksi Modal --}}
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="showModalTambahPelanggan=false"
                        class="px-4 py-2 rounded-lg border border-slate-300">Batal</button>
                    <button type="button" @click="savePelangganBaru"
                        class="px-4 py-2 rounded-lg bg-[#334976] hover:bg-[#2d3f6d] text-white w-full">Simpan</button>
                </div>
            </div>
        </div>


        <div x-show="showPrintModal" x-cloak
            class="fixed inset-0 bg-black/40 min-h-screen flex items-center justify-center z-50">
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

                lebarThreshold: 10000000,

                allItems: [],
                savedPenjualanId: null,
                showPrintModal: false,

                // === NOTIFIKASI STATE ===
                notifMessage: '',
                notifType: '',
                showNotif: false,

                // === INIT ===
                init() {
                    this.form.no_faktur = this.$el.getAttribute('data-no-faktur') || '';
                    this.form.tanggal = this.$el.getAttribute('data-tanggal') || '';
                    this.subTotal = 0;
                    this.totalPembayaran = 0;
                    this.form.items = [];
                    this.allItems = @json($itemsJson ?? []);

                    this.setupSmartScannerFocus();
                    this.recalc();
                },

                // === JUMLAH DENGAN FORMAT (MENDUKUNG 1 KOMA) ===
                updateJumlahFormatted(idx, val) {
                    // pastikan val adalah string
                    val = (val || '').toString();

                    // jika user mulai dengan koma seperti ",5" => ubah ke "0,5"
                    if (val.startsWith(',')) val = '0' + val;

                    // hapus semua kecuali digit dan koma
                    val = val.replace(/[^0-9,]/g, '');

                    // pisah kiri/kanan berdasarkan koma
                    let parts = val.split(',');

                    // jika lebih dari satu koma, gabungkan sisanya jadi satu (ambil dua bagian teratas)
                    if (parts.length > 2) {
                        parts = [parts[0], parts.slice(1).join('')];
                    }

                    // bagian kiri (angka bulat) — buang leading zeros (kecuali satu digit '0')
                    parts[0] = parts[0].replace(/^0+(?=\d)/, '');

                    // format ribuan pada bagian kiri
                    const leftFormatted = (parts[0] || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                    // bagian desimal (jika ada) hanya berisi digit
                    if (parts[1]) {
                        parts[1] = parts[1].replace(/[^0-9]/g, '');
                    }

                    // gabungkan untuk tampilan sementara (Alpine akan meng-overwrite input dari :value)
                    const formatted = parts.length > 1 ? `${leftFormatted},${parts[1]}` : leftFormatted;

                    // Simpan nilai numeric ke model (gunakan titik sebagai pemisah desimal)
                    const numericStr = (parts[0] ? parts[0].replace(/\./g, '') : '0') + (parts[1] ? '.' + parts[1] : '');
                    const numeric = parseFloat(numericStr) || 0;
                    this.form.items[idx].jumlah = numeric;

                    // Hitung ulang totals
                    this.recalc();

                    // NOTE: input akan diperbarui oleh :value binding (formatJumlah)
                    // Jika ingin segera menimpa input secara manual, kamu bisa:
                    // event.target.value = formatted
                    // tapi karena kita tidak menerima event di sini, biarkan Alpine yang re-render.
                },

                // Format tampilan jumlah (dipakai oleh :value)
                formatJumlah(val) {
                    if (val == null || val === '') return '';
                    // pastikan string (jaga agar desimal tetap seperti yang tersimpan)
                    const s = val.toString();
                    const parts = s.split('.');
                    const intPart = (parts[0] || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const decPart = parts[1] || '';
                    return decPart ? `${intPart},${decPart}` : intPart;
                },

                // === SMART SCANNER FOCUS ===
                setupSmartScannerFocus() {
                    const barcodeInput = this.$refs.barcodeInput;
                    if (!barcodeInput) return;

                    // Fokuskan scanner saat awal load
                    this.focusScanner();

                    // Jika user klik area kosong (bukan input)
                    window.addEventListener('click', (e) => {
                        const tag = e.target.tagName?.toLowerCase();
                        if (!['input', 'textarea', 'select'].includes(tag)) this.focusScanner();
                    });

                    // Jika user keluar dari input manual → aktifkan scanner lagi
                    document.addEventListener('focusout', () => {
                        setTimeout(() => {
                            const active = document.activeElement;
                            if (!active || !active.tagName) return;
                            const tag = active.tagName.toLowerCase();
                            if (!['input', 'textarea', 'select'].includes(tag)) this.focusScanner();
                        }, 150);
                    });
                },

                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                },

                // === NOTIFIKASI ===
                notify(msg, type = 'info') {
                    this.notifMessage = msg;
                    this.notifType = type;
                    this.showNotif = true;
                    setTimeout(() => (this.showNotif = false), 3000);
                },

                // === WATCH MODE ===
                watchMode() {
                    if (this.form.mode === 'ambil') this.form.biaya_transport = 0;
                    this.updateAllItemPrices();
                    this.recalc();
                },

                // === HARGA ===
                getPriceFromSelected(selected, level, is_walkin, mode) {
                    if (!selected) return 0;
                    if (!level || is_walkin) return Number(selected.harga_retail || 0);

                    level = level.toLowerCase();

                    if (level === 'grosir') {
                        return mode === 'ambil' ?
                            Number(selected.harga_partai_kecil || selected.harga_retail || 0) :
                            Number(selected.harga_grosir || selected.harga_retail || 0);
                    }

                    if (level === 'partai_kecil') {
                        return mode === 'ambil' ?
                            Number(selected.harga_partai_kecil || selected.harga_retail || 0) :
                            Number(selected.harga_grosir || selected.harga_retail || 0);
                    }

                    return Number(selected.harga_retail || 0);
                },

                // === TAMBAH ITEM MANUAL ===
                addItemManual() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        keterangan: '', // ✅ baru
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
                        if (!item.satuan_id) {
                            item.satuan_id = item.filteredSatuans[0].satuan_id;
                        }
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        item.harga = 0;
                    }
                },

                getHargaByLevel(g) {
                    if (!g) return 0;
                    const level = this.selectedPelangganLevel || 'retail';
                    const mode = this.form.mode;

                    if (level === 'grosir') {
                        return mode === 'ambil' ?
                            (g.harga_partai_kecil || g.harga_retail || 0) :
                            (g.harga_grosir || g.harga_retail || 0);
                    }

                    if (level === 'partai_kecil') {
                        return mode === 'ambil' ?
                            (g.harga_partai_kecil || g.harga_retail || 0) :
                            (g.harga_grosir || g.harga_retail || 0);
                    }

                    return g.harga_retail || 0;
                },


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

                    this.recalc();
                },


                updateAllItemPrices() {
                    this.form.items.forEach((item, idx) => {
                        this.updateStockAndPrice(idx);
                        if (!item.harga_manual && item.satuan_id) this.fetchPriceForItem(idx);
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
                        this.pelangganResults = await res.json();
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
                    this.selectedPelangganLevel = (p.level || 'retail').toLowerCase();
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
                        this.notify('Nama pelanggan wajib diisi', 'error');
                        return;
                    }

                    try {
                        const res = await fetch('/pelanggan/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(this.newPelanggan)
                        });

                        if (!res.ok) throw new Error('Gagal simpan pelanggan');
                        const saved = await res.json();

                        this.form.pelanggan_id = saved.id;
                        this.selectedPelangganNames = saved.nama_pelanggan;
                        this.selectedPelangganLevel = (saved.level || 'retail').toLowerCase();
                        this.form.is_walkin = false;
                        this.showModalTambahPelanggan = false;

                        this.updateAllItemPrices();
                        this.notify('Pelanggan berhasil ditambahkan!', 'success');
                    } catch (err) {
                        this.notify('Gagal menyimpan pelanggan baru', 'error');
                        console.error(err);
                    }
                },

                // === SCANNER ===
                async handleBarcode(e) {
                    const code = e.target.value.trim();
                    if (!code) return;

                    try {
                        const res = await fetch(`/items/barcode/${encodeURIComponent(code)}`);
                        if (!res.ok) {
                            this.notify(`Item dengan kode "${code}" tidak ditemukan`, 'error');
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
                                keterangan: '', // ✅ baru
                                gudang_id: data.gudangs?.[0]?.gudang_id || '',
                                gudangs: data.gudangs || [],
                                satuan_id: '',
                                filteredSatuans: [],
                                jumlah: 1,
                                harga: 0,
                                stok: 0,
                                results: [],
                                harga_manual: false
                            });


                            const idx = this.form.items.length - 1;
                            this.updateSatuanOptions(idx); // ✅ otomatis ambil satuan & harga
                        }

                        this.recalc();
                        this.notify(`${data.nama_item} ditambahkan`, 'success');
                    } catch (err) {
                        console.error("Error handleBarcode:", err);
                        this.notify('Terjadi kesalahan saat memproses barcode', 'error');
                    } finally {
                        e.target.value = '';
                        setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                    }
                },

                handlePelangganClickAway() {
                    this.openResults = false;
                    if (!this.form.pelanggan_id && this.pelangganQuery) {
                        this.selectedPelangganNames = 'Customer';
                        this.selectedPelangganLevel = null;
                        this.form.is_walkin = true;
                        this.updateAllItemPrices();
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
                        const level = (this.selectedPelangganLevel || 'retail').toLowerCase();
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

                // === PERHITUNGAN TOTAL ===
                recalc() {
                    this.subTotal = this.form.items.reduce((sum, i) =>
                        sum + ((parseFloat(i.jumlah) || 0) * (parseFloat(i.harga) || 0)), 0);
                    const transport = this.form.mode === 'antar' ?
                        (parseFloat(this.form.biaya_transport) || 0) :
                        0;
                    this.totalPembayaran = this.subTotal + transport;
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
                    return this.form.items.every(i =>
                        i.item_id && i.gudang_id && i.satuan_id && i.jumlah > 0 && i.harga >= 0
                    );
                },

                async save() {
                    if (!this.isValid()) {
                        this.notify('Mohon lengkapi semua data item penjualan.', 'error');
                        return;
                    }

                    for (const item of this.form.items) {
                        if (item.jumlah > item.stok) {
                            this.notify(`Stok tidak cukup untuk item: ${item.query}`, 'error');
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
                            total: parseFloat(i.jumlah) * parseFloat(i.harga),
                            keterangan: i.keterangan || '' // ✅ baru
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
                            this.notify('Gagal menyimpan penjualan: ' + (result.message || 'Unknown error'), 'error');
                            return;
                        }

                        this.savedPenjualanId = result.id;
                        this.showPrintModal = true;
                    } catch (err) {
                        console.error('Error save:', err);
                        this.notify('Terjadi kesalahan saat menyimpan penjualan.', 'error');
                    }
                },

                async saveDraft() {
                    if (this.form.items.length === 0) {
                        this.notify('Minimal harus ada 1 item untuk disimpan.', 'error');
                        return;
                    }

                    const payload = {
                        id: this.form.id,
                        pelanggan_id: this.form.pelanggan_id,
                        no_faktur: this.form.no_faktur,
                        tanggal: this.form.tanggal,
                        deskripsi: this.form.deskripsi,
                        biaya_transport: this.form.biaya_transport || 0,
                        mode: this.form.mode,
                        sub_total: this.subTotal || 0,
                        total: this.totalPembayaran || 0,
                        is_draft: true,
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
                        const res = await fetch(`/penjualan/store`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            this.notify('Gagal menyimpan draft: ' + (result.message || 'Unknown error'), 'error');
                            return;
                        }

                        this.notify('Penjualan disimpan sebagai pending.', 'success');
                        window.location.href = '/penjualan';
                    } catch (err) {
                        console.error('Error saveDraft:', err);
                        this.notify('Terjadi kesalahan saat menyimpan pending.', 'error');
                    }
                },
                resetForm() {
                    this.form.items = [];
                    this.subTotal = 0;
                    this.totalPembayaran = 0;
                    this.form.deskripsi = '';
                    this.form.pelanggan_id = null;
                    this.selectedPelangganNames = '';
                    this.selectedPelangganLevel = null;
                }
            }
        }
    </script>


@endsection
