@extends('layouts.app')

@section('title', 'Detail Penjualan')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div x-data="penjualanShowPage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('penjualan.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- Hidden Input Scanner --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        {{-- Form Utama --}}
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
                                @blur="
                            if (!form.pelanggan_id && pelangganQuery && pelangganResults.length === 0) {
                                selectedPelangganNames = 'Customer';
                                selectedPelangganLevel = null;
                                form.pelanggan_id = null;
                                form.is_walkin = true;
                            }
                        "
                                @focus="openResults = (pelangganQuery.length >= 2)" placeholder="Cari pelanggan"
                                class="w-full pl-4 pr-12 py-2.5 rounded-lg border border-slate-300
                            focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                            {{-- Icon pencarian di kanan --}}
                            <span x-show="!form.pelanggan_id" x-cloak x-transition.opacity.duration.150ms
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            {{-- Badge di dalam input --}}
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
            {{-- Header --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-semibold text-slate-800">Daftar Item Penjualan</h3>
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
                                <!-- Nomor urut -->
                                <td class="px-5 py-4 text-center font-medium align-middle" x-text="idx + 1"></td>

                                <!-- ===========================
                                                                                                                                                                                         Nama Item + Tombol Keterangan
                                                                                                                                                                                         ============================ -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative">
                                        <div class="flex items-center gap-2">
                                            <!-- ⭐ TOMBOL KETERANGAN -->
                                            <button type="button" @click="toggleItemNote(idx)"
                                                :title="item.showNote ? 'Sembunyikan keterangan' : 'Tambah keterangan'"
                                                :class="{
                                                    'text-blue-600': item.showNote,
                                                    'text-slate-500 hover:text-blue-600': !item.showNote
                                                }"
                                                class="transition focus:outline-none">
                                                <i class="fa-solid fa-note-sticky text-[15px]"></i>
                                            </button>

                                            <!-- Input cari item -->
                                            <div class="relative flex-1">
                                                <input type="text" x-model="item.query"
                                                    @input.debounce.300ms="
                        if (item.query.length >= 2) {
                            searchItem(idx);
                            item._dropdownOpen = true;
                        } else {
                            item.item_id = null;
                            item.gudang_id = '';
                            item.satuan_id = '';
                            item.gudangs = [];
                            item.filteredSatuans = [];
                            item.stok = 0;
                            item.harga = 0;
                            item.results = [];
                            item._dropdownOpen = false;
                        }"
                                                    @focus="item._dropdownOpen = (item.query && item.query.length >= 2)"
                                                    @click="item._dropdownOpen = (item.query && item.query.length >= 2)"
                                                    @keydown.escape="item._dropdownOpen = false" placeholder="Cari item"
                                                    class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                                <!-- Icon pencarian -->
                                                <span x-show="!item.item_id" x-cloak x-transition.opacity.duration.150ms
                                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                                    <i class="fa-solid fa-magnifying-glass"></i>
                                                </span>

                                                <!-- Dropdown hasil pencarian -->
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
                                                            <div class="text-xs text-slate-500" x-text="r.kode_item">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ✅ FORM KETERANGAN - SAMA SEPERTI CREATE -->
                                        <div x-show="item.showNote" x-transition.opacity.duration.300ms x-cloak
                                            class="mt-3 space-y-3">

                                            <!-- Form untuk Item Spandek -->
                                            <template x-if="item.is_spandek === true">
                                                <div class="space-y-3">
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                                            Keterangan <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text" x-model="item.keterangan"
                                                            placeholder="Contoh: Panjang 6m, Lebar 1m"
                                                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                                            Jenis Spandek <span class="text-red-500">*</span>
                                                        </label>
                                                        <select x-model="item.catatan_produksi"
                                                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition bg-white">
                                                            <option value="">-- Pilih jenis spandek --</option>
                                                            <option value="Spandek Biasa">Spandek Biasa</option>
                                                            <option value="Spandek Pasir">Spandek Pasir</option>
                                                            <option value="Spandek Laminasi">Spandek Laminasi</option>
                                                            <option value="Spandek Warna">Spandek Warna</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- Form untuk Item Biasa -->
                                            <template x-if="item.is_spandek === false">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                                        Keterangan
                                                    </label>
                                                    <input type="text" x-model="item.keterangan"
                                                        placeholder="Catatan tambahan (opsional)"
                                                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                                </div>
                                            </template>

                                            <!-- Fallback: Item belum dipilih -->
                                            <template x-if="item.is_spandek === undefined || item.is_spandek === null">
                                                <div
                                                    class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-center">
                                                    <small class="text-amber-700 text-xs">
                                                        Pilih item terlebih dahulu
                                                    </small>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </td>

                                <!-- Gudang -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative w-full">
                                        <div
                                            class="border border-slate-300 rounded-lg px-3 pr-8 py-[6px] text-sm text-slate-700 
                                        focus-within:ring-2 focus-within:ring-[#344579]/20 focus-within:border-[#344579] transition">
                                            <div class="flex flex-col leading-tight">
                                                <div class="text-[13px] text-slate-700">
                                                    <span
                                                        x-text="(getDistinctGudangs(item).find(g => g.gudang_id == item.gudang_id) || getDistinctGudangs(item)[0] || {}).nama_gudang || '-'">
                                                    </span>
                                                    <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                                        class="absolute inset-0 opacity-0 cursor-pointer">
                                                        <template x-for="g in getDistinctGudangs(item)"
                                                            :key="g.gudang_id">
                                                            <option :value="g.gudang_id" x-text="g.nama_gudang"></option>
                                                        </template>
                                                    </select>
                                                </div>
                                                <div
                                                    :class="(item.gudang_id && (parseFloat(item.stok) === 0)) ?
                                                    'text-rose-600 font-semibold text-[11px] mt-[1px]' :
                                                    'text-slate-500 text-[11px] mt-[1px]'">
                                                    Stok: <span
                                                        x-text="item.gudang_id ? formatStok(item.stok) : ''"></span>
                                                </div>
                                            </div>
                                        </div>
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



        {{-- Ringkasan & Aksi --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp <span x-text="formatRupiah(subTotal)"></span>
                    </div>
                </div>

                <div x-show="form.mode === 'antar'" class="mb-4">
                    <label class="text-slate-600 text-sm mb-1 block">Biaya Transportasi</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="text" :value="formatRupiah(form.biaya_transport)"
                            @input="updateTransport($event.target.value)" placeholder="0"
                            class="pl-10 pr-3 w-full border border-slate-300 rounded-lg px-3 py-2.5 text-right focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-blue-500" />
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-4 mt-4"></div>

                <div class="flex justify-between items-center mb-6">
                    <div class="text-slate-700 font-bold text-lg">TOTAL PENJUALAN</div>
                    <div class="text-blue-700 text-2xl font-extrabold tracking-wide">
                        Rp <span x-text="formatRupiah(totalPembayaran)"></span>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6">
                    <!-- Mode Draft (Pending) -->
                    <template x-if="form.is_draft == 1">
                        <div class="flex gap-3">
                            <button @click="cancelDraft"
                                class="px-5 py-2.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-500 hover:text-white transition">
                                Batal
                            </button>

                            <button @click="update" :disabled="!isDirty || isSaving"
                                :class="[
                                    'px-5 py-2.5 rounded-lg text-white font-medium shadow-sm transition',
                                    (!isDirty || isSaving) ?
                                    'bg-gray-400 cursor-not-allowed' :
                                    'bg-[#334976] hover:bg-[#2d3f6d] hover:shadow-md'
                                ]">
                                Simpan
                            </button>
                        </div>
                    </template>

                    <!-- Mode Final (Non-Draft) -->
                    <template x-if="form.is_draft == 0">
                        <div class="flex gap-3 w-full">
                            <button @click="goBack"
                                class="px-5 py-2.5 rounded-lg border border-gray-400 text-gray-600 hover:bg-gray-100 transition">
                                Kembali
                            </button>

                            <button @click="saveOrUpdate" :disabled="!isDirty || isSaving"
                                :class="[
                                    'px-5 py-2.5 rounded-lg text-white font-medium w-full transition',
                                    (!isDirty || isSaving) ?
                                    'bg-gray-200 w-full cursor-not-allowed' :
                                    'bg-[#334976] hover:bg-[#2d3f6d] hover:shadow-md'
                                ]">
                                Simpan Perubahan
                            </button>
                        </div>
                    </template>
                </div>


            </div>
        </div>

        <!-- 🧾 Modal Cetak Nota -->
        <div x-show="initialized && showPrintModal" x-cloak aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen">

            <!-- 🌫 Overlay -->
            <div x-show="showPrintModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all"></div>

            <!-- 💎 Modal Card -->
            <div x-show="showPrintModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                class="relative bg-white/95 backdrop-blur-sm w-[420px]
               rounded-2xl shadow-[0_10px_35px_-5px_rgba(51,73,118,0.25)]
               border border-slate-200 transform transition-all overflow-hidden"
                @click.away="showPrintModal = false">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] 
            border-b border-slate-200 px-5 py-3 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-base font-semibold text-[#334976] flex items-center gap-2">
                        <i class="fa-solid fa-print text-[#334976]"></i>
                        Penjualan Berhasil Disimpan
                    </h3>
                    <button @click="showPrintModal = false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-3 bg-white">
                    <p class="text-slate-600 mb-4">Pilih opsi cetak:</p>

                    <!-- ✅ GANTI <a> jadi <button> dengan @click -->
                    <button @click="printNota('kecil')" type="button"
                        class="w-full px-4 py-2.5 rounded-lg text-white bg-blue-600 hover:bg-blue-700
                font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-receipt mr-2"></i> Print Nota Kecil
                    </button>

                    <button @click="printNota('besar')" type="button"
                        class="w-full px-4 py-2.5 rounded-lg text-white bg-green-600 hover:bg-green-700
                font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-file-invoice mr-2"></i> Print Nota Besar
                    </button>
                </div>

                <!-- Footer -->
                <div class="flex justify-end px-6 py-4 bg-slate-50 border-t border-slate-200 rounded-b-2xl">
                    <button type="button" @click="window.location.href = '/penjualan'"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 
                hover:bg-slate-100 transition font-medium">
                        Kembali
                    </button>
                </div>
            </div>
        </div>



        {{-- Modal Tambah Pelanggan --}}
        <div x-show="showModalTambahPelanggan" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen" aria-modal="true" role="dialog">
            {{-- 🩶 Overlay --}}
            <div x-show="showModalTambahPelanggan" x-transition.opacity.duration.300ms
                class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

            {{-- 💠 Modal Card --}}
            <div x-show="showModalTambahPelanggan" x-transition.opacity.duration.200ms x-transition.scale.duration.250ms
                class="relative bg-white w-[420px] rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all">
                {{-- Header --}}
                <div class="bg-slate-50 border-b border-slate-200 px-5 py-3 flex justify-between items-center">
                    <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-blue-600"></i>
                        Tambah Pelanggan Baru
                    </h3>
                    <button @click="showModalTambahPelanggan = false"
                        class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- Body --}}
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nama Pelanggan <span
                                class="text-rose-500">*</span></label>
                        <input type="text" x-model="newPelanggan.nama_pelanggan"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                            placeholder="Masukkan nama pelanggan">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Kontak</label>
                        <input type="text" x-model="newPelanggan.kontak"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                            placeholder="Nomor HP / WhatsApp">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Alamat</label>
                        <textarea x-model="newPelanggan.alamat" rows="2"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                            placeholder="Alamat pelanggan (opsional)"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Level</label>
                        <select x-model="newPelanggan.level"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700
                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition bg-white">
                            <option value="retail">Retail</option>
                            <option value="grosir">Grosir</option>
                        </select>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200">
                    <button @click="showModalTambahPelanggan = false"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition font-medium">
                        Batal
                    </button>

                    <button @click="savePelangganBaru"
                        class="px-5 py-2.5 rounded-lg font-medium text-white bg-[#334976] hover:bg-[#2d3f6d] 
                       shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-save mr-1.5"></i> Simpan
                    </button>
                </div>
            </div>
        </div>

    </div>




    @php
        $itemsJson = \App\Models\Item::with(['gudangItems.gudang', 'gudangItems.satuan', 'kategori']) // ✅ TAMBAHKAN 'kategori'
            ->get()
            ->map(function ($i) {
                return [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'kategori' => $i->kategori?->nama_kategori ?? '', // ✅ TAMBAHKAN kategori
                    'gudangs' => $i->gudangItems->map(function ($ig) {
                        return [
                            'gudang_id' => $ig->gudang?->id,
                            'nama_gudang' => $ig->gudang?->nama_gudang,
                            'satuan_id' => $ig->satuan?->id,
                            'nama_satuan' => $ig->satuan?->nama_satuan,
                            'stok' => $ig->stok,
                            'harga_retail' => $ig->satuan?->harga_retail ?? 0,
                            'harga_partai_kecil' => $ig->satuan?->partai_kecil ?? 0,
                            'harga_grosir' => $ig->satuan?->harga_grosir ?? 0,
                        ];
                    }),
                ];
            })
            ->toArray();
    @endphp


    <script>
        function penjualanShowPage() {
            return {
                // === STATE ===
                isDraft: {{ (int) $penjualan->is_draft }}, // ✅ ganti dari isPending ke isDraft (0/1)
                pelangganQuery: @json(optional($penjualan->pelanggan)->nama_pelanggan ?? 'Customer'),
                pelangganResults: [],
                pelangganLoading: false,
                openResults: false,
                selectedPelangganNames: @json(optional($penjualan->pelanggan)->nama_pelanggan ?? 'Customer'),
                selectedPelangganLevel: @json(optional($penjualan->pelanggan)->level ?? 'retail'),

                showModalTambahPelanggan: false,
                newPelanggan: {
                    nama_pelanggan: '',
                    kontak: '',
                    alamat: '',
                    level: 'retail'
                },

                form: {
                    pelanggan_id: {{ $penjualan->pelanggan_id ?? 'null' }},
                    id: {{ $penjualan->id }},
                    mode: {{ Js::from($penjualan->mode ?? 'ambil') }},
                    no_faktur: {{ Js::from($penjualan->no_faktur) }},
                    tanggal: {{ Js::from(optional($penjualan->tanggal)->format('Y-m-d')) }},
                    biaya_transport: {{ (int) $penjualan->biaya_transport }},
                    is_draft: {{ (int) $penjualan->is_draft }},
                    items: []
                },

                subTotal: 0,
                totalPembayaran: 0,
                allItems: [],
                savedPenjualanId: null,
                showPrintModal: false,
                isDirty: false,
                isSaving: false,
                initialForm: null,
                initialized: false,

                // === TOAST ===
                showToast(msg, type = 'success') {
                    const bg = type === 'error' ?
                        'bg-rose-50 text-rose-700 border border-rose-200' :
                        'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    const icon = type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check';
                    const el = document.createElement('div');
                    el.className =
                        `fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-md border shadow ${bg}`;
                    el.innerHTML = `<i class="fa-solid ${icon}"></i><span>${msg}</span>`;
                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3500);
                },

                // === INIT ===
                init() {
                    this.allItems = @json($itemsJson);

                    this.form.items = [];

                    if (!this.form.pelanggan_id) {
                        this.selectedPelangganNames = 'Customer';
                        this.pelangganQuery = 'Customer';
                        this.selectedPelangganLevel = 'retail';
                    }

                    @foreach ($penjualan->items as $it)
                        this.form.items.push({
                            item_id: {{ $it->item_id }},
                            query: {{ Js::from($it->item->nama_item ?? '') }},
                            kategori: {{ Js::from(optional($it->item->kategori)->nama_kategori ?? '') }},
                            keterangan: {{ Js::from($it->keterangan ?? '') }},
                            catatan_produksi: {{ Js::from($it->catatan_produksi ?? '') }},
                            gudang_id: {{ $it->gudang_id }},
                            satuan_id: {{ $it->satuan_id }},
                            jumlah: {{ $it->jumlah }},
                            harga: {{ $it->harga }},
                            stok: 0,
                            harga_manual: false,
                            showNote: false, // ✅ TAMBAHKAN
                            _dropdownOpen: false, // ✅ TAMBAHKAN
                            gudangs: {!! json_encode(
                                $it->item->gudangItems->map(
                                        fn($ig) => [
                                            'gudang_id' => $ig->gudang_id,
                                            'nama_gudang' => $ig->gudang->nama_gudang ?? '',
                                            'satuan_id' => $ig->satuan_id,
                                            'nama_satuan' => $ig->satuan->nama_satuan ?? '',
                                            'stok' => $ig->stok ?? 0,
                                            'harga_retail' => $ig->satuan->harga_retail ?? 0,
                                            'harga_partai_kecil' => $ig->satuan->partai_kecil ?? 0,
                                            'harga_grosir' => $ig->satuan->harga_grosir ?? 0,
                                        ],
                                    )->toArray(),
                            ) !!},
                            filteredSatuans: [],
                            results: [],
                            is_spandek: {{ Js::from(
                                str_contains(strtolower(optional($it->item->kategori)->nama_kategori ?? ''), 'spandek') ||
                                    str_contains(strtolower(optional($it->item->kategori)->nama_kategori ?? ''), 'spandex'),
                            ) }}
                        });
                    @endforeach

                    this.$nextTick(() => {
                        this.form.items.forEach((item, idx) => {
                            this.updateSatuanOptions(idx);
                            this.updateStockAndPrice(idx);
                        });
                        this.recalc();
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.initialized = true;
                        this.watchFormChanges();
                        this.watchModeChange();

                    });

                    this.setupSmartScannerFocus();
                },

                addItemManual() {
                    if (!Array.isArray(this.form.items)) {
                        this.form.items = [];
                    }

                    this.form.items = [
                        ...this.form.items,
                        {
                            item_id: null,
                            query: '',
                            kategori: '',
                            is_spandek: false, // ✅ WAJIB
                            showNote: false, // ✅ WAJIB
                            _dropdownOpen: false, // ✅ WAJIB
                            keterangan: '',
                            catatan_produksi: '',
                            gudang_id: '',
                            gudangs: [],
                            satuan_id: '',
                            filteredSatuans: [],
                            jumlah: 1,
                            harga: 0,
                            stok: 0,
                            results: [],
                            harga_manual: false
                        }
                    ];

                    this.recalc();
                },

                // ✅ TAMBAHKAN function ini
                toggleItemNote(idx) {
                    const item = this.form.items[idx];
                    if (!item) return;

                    item.showNote = !item.showNote;

                    if (item.showNote && item.is_spandek && (!item.keterangan || !item.catatan_produksi)) {
                        this.showToast('Untuk item spandek, isi KEDUA field: keterangan dan jenis spandek', 'info');
                    }
                },

                // === WATCH FORM ===
                watchFormChanges() {
                    this.$watch('form', (newVal) => {
                        if (!this.initialized) return;
                        this.isDirty = JSON.stringify(newVal) !== JSON.stringify(this.initialForm);
                    }, {
                        deep: true
                    });
                },

                // === SMART SCANNER ===
                setupSmartScannerFocus() {
                    const barcodeInput = this.$refs.barcodeInput;
                    if (!barcodeInput) return;
                    this.focusScanner();
                    window.addEventListener('click', (e) => {
                        const tag = e.target.tagName.toLowerCase();
                        if (!['input', 'textarea', 'select'].includes(tag)) this.focusScanner();
                    });
                },
                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                },

                // === SCANNER ===
                handleBarcode(e) {
                    const kode = e.target.value.trim();
                    if (!kode) return;

                    const found = this.allItems.find(i => i.kode_item?.toLowerCase() === kode.toLowerCase());
                    if (!found) {
                        this.showToast(`Item dengan kode "${kode}" tidak ditemukan`, 'error');
                        e.target.value = '';
                        return;
                    }

                    const existing = this.form.items.find(i => i.item_id === found.id);
                    if (existing) {
                        existing.jumlah = (parseFloat(existing.jumlah) || 0) + 1;
                        this.recalc();
                    } else {
                        const firstGudang = found.gudangs?.[0] || {};
                        const kategori = found.kategori || '';
                        const isSpandek = kategori &&
                            (kategori.toLowerCase().includes('spandek') ||
                                kategori.toLowerCase().includes('spandex'));

                        this.form.items.push({
                            item_id: found.id,
                            query: found.nama_item,
                            kategori: kategori,
                            is_spandek: isSpandek, // ✅ TAMBAHKAN
                            showNote: false, // ✅ TAMBAHKAN
                            _dropdownOpen: false, // ✅ TAMBAHKAN
                            keterangan: '',
                            catatan_produksi: '',
                            gudang_id: firstGudang.gudang_id || '',
                            satuan_id: firstGudang.satuan_id || '',
                            jumlah: 1,
                            harga: this.getHargaByLevel(firstGudang),
                            stok: firstGudang.stok || 0,
                            gudangs: found.gudangs || [],
                            filteredSatuans: found.gudangs?.filter(g => g.gudang_id === firstGudang.gudang_id) ||
                            [],
                            results: [],
                            harga_manual: false
                        });
                        this.recalc();
                    }

                    e.target.value = '';
                    this.focusScanner();
                },

                // === JUMLAH & FORMAT ===
                updateJumlahFormatted(idx, val) {
                    val = (val || '').toString().replace(/[^0-9,]/g, '');
                    if (val.startsWith(',')) val = '0' + val;
                    const parts = val.split(',');
                    const numeric = parseFloat(parts[0].replace(/\./g, '') + (parts[1] ? '.' + parts[1] : '')) || 0;
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

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.form.items = [...this.form.items]; // ⬅️ paksa Alpine reactive update
                },


                // === PELANGGAN ===
                async searchPelanggan() {
                    if (this.pelangganQuery.length < 2) return this.pelangganResults = [];
                    this.pelangganLoading = true;
                    try {
                        const res = await fetch(`/pelanggan/search?q=${encodeURIComponent(this.pelangganQuery)}`);
                        this.pelangganResults = await res.json();
                    } catch {
                        this.pelangganResults = [];
                    } finally {
                        this.pelangganLoading = false;
                    }
                },
                selectPelanggan(p) {
                    this.form.pelanggan_id = p.id;
                    this.selectedPelangganNames = p.nama_pelanggan;
                    this.selectedPelangganLevel = p.level?.toLowerCase() || 'retail';
                    this.pelangganQuery = p.nama_pelanggan;
                    this.form.is_walkin = false;
                    this.openResults = false;
                    this.updateAllItemPrices();
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

                // === WATCH MODE (update harga realtime saat ganti mode) ===
                watchModeChange() {
                    this.$watch('form.mode', (newMode, oldMode) => {
                        if (newMode !== oldMode && this.initialized) {
                            this.updateAllItemPrices();
                            this.showToast(
                                `Mode pengiriman diubah ke "${newMode === 'ambil' ? 'Ambil Sendiri' : 'Butuh Pengiriman'}"`
                            );
                        }
                    });
                },


                // === ITEM HANDLER ===
                searchItem(idx) {
                    const item = this.form.items[idx];
                    if (!item.query || item.query.length < 2) {
                        item.results = [];
                        return;
                    }
                    const q = item.query.toLowerCase();
                    item.results = this.allItems.filter(i =>
                        i.nama_item.toLowerCase().includes(q) ||
                        i.kode_item?.toLowerCase().includes(q)
                    ).slice(0, 20);
                },
                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.kategori = item.kategori || '';
                    row.gudangs = item.gudangs || [];
                    row.harga_manual = false;

                    // ✅ JANGAN RESET showNote - pertahankan nilai lama
                    if (row.showNote === undefined) {
                        row.showNote = false;
                    }

                    // ✅ Set is_spandek berdasarkan kategori
                    row.is_spandek = row.kategori &&
                        (row.kategori.toLowerCase().includes('spandek') ||
                            row.kategori.toLowerCase().includes('spandex'));

                    // ✅ Reset keterangan hanya jika item baru dipilih
                    if (!row.keterangan) {
                        row.keterangan = '';
                        row.catatan_produksi = '';
                    }

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

                getHargaByLevel(g) {
                    if (!g) return 0;

                    const level = this.selectedPelangganLevel || 'retail';
                    const mode = this.form.mode;

                    // Jika pelanggan tidak dipilih → harga retail
                    if (!this.form.pelanggan_id || this.form.is_walkin) {
                        return g.harga_retail || 0;
                    }

                    // Mode: Ambil Sendiri
                    if (mode === 'ambil') {
                        if (level === 'retail') return g.harga_retail || 0;
                        if (level === 'partai_kecil') return g.harga_partai_kecil || g.harga_retail;
                        if (level === 'grosir') return g.harga_partai_kecil || g.harga_retail;
                    }

                    // Mode: Butuh Pengiriman
                    if (mode === 'antar') {
                        if (level === 'retail') return g.harga_retail || 0;
                        if (level === 'partai_kecil') return g.harga_grosir || g.harga_partai_kecil || g.harga_retail;
                        if (level === 'grosir') return g.harga_grosir || g.harga_partai_kecil || g.harga_retail;
                    }

                    // Default fallback
                    return g.harga_retail || 0;
                },

                updateAllItemPrices() {
                    this.form.items.forEach((item) => {
                        const selected = item.gudangs.find(g => g.gudang_id == item.gudang_id && g.satuan_id == item
                            .satuan_id);
                        if (selected && !item.harga_manual) item.harga = this.getHargaByLevel(selected);
                    });
                    this.recalc();
                },

                // === GUDANG & SATUAN ===
                getDistinctGudangs(item) {
                    if (!item.gudangs?.length) return [];
                    const seen = new Set();
                    return item.gudangs.filter(g => !seen.has(g.gudang_id) && seen.add(g.gudang_id));
                },
                updateSatuanOptions(idx) {
                    const item = this.form.items[idx];
                    if (!item.gudangs?.length) return item.filteredSatuans = [];
                    item.filteredSatuans = item.gudangs.filter(g => g.gudang_id == item.gudang_id);
                    if (item.filteredSatuans.length) {
                        if (!item.satuan_id) item.satuan_id = item.filteredSatuans[0].satuan_id;
                        this.updateStockAndPrice(idx);
                    } else {
                        item.satuan_id = '';
                        item.stok = 0;
                        item.harga = 0;
                    }
                },
                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id);
                    if (selected) {
                        item.stok = selected.stok || 0;
                        item.harga = this.getHargaByLevel(selected);
                    } else {
                        item.stok = 0;
                        item.harga = 0;
                    }
                    this.recalc();
                },

                // === PERHITUNGAN ===
                updateManualPrice(idx, val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.items[idx].harga = parseInt(clean) || 0;
                    this.form.items[idx].harga_manual = true;
                    this.recalc();
                },
                updateTransport(val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.biaya_transport = parseInt(clean) || 0;
                    this.recalc();
                },
                recalc() {
                    let subtotal = 0;
                    this.form.items.forEach(i => {
                        subtotal += (parseFloat(i.jumlah) || 0) * (parseFloat(i.harga) || 0);
                    });
                    this.subTotal = subtotal;
                    const transport = this.form.mode === 'antar' ? (this.form.biaya_transport || 0) : 0;
                    this.totalPembayaran = subtotal + transport;
                },

                // === UTIL ===
                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID').format(n || 0);
                },
                formatLevel(level) {
                    return {
                        retail: 'Retail',
                        partai_kecil: 'Partai Kecil',
                        grosir: 'Grosir'
                    } [level] || '-';
                },
                formatStok(val) {
                    const num = parseFloat(val) || 0;
                    return Number.isInteger(num) ? num : num.toLocaleString('id-ID');
                },

                // === UPDATE ===
                async update() {
                    if (this.isSaving) return;
                    this.isSaving = true;
                    try {
                        await this.saveOrUpdate(false);
                    } finally {
                        this.isSaving = false;
                    }
                },

                async saveOrUpdate() {
                    if (!this.form.items.length)
                        return this.showToast('Minimal harus ada 1 item', 'error');

                    // ✅ Validasi spandek (wajib isi catatan_produksi)
                    for (const item of this.form.items) {
                        if (item.is_spandek && !item.catatan_produksi) {
                            this.showToast(`Jenis spandek wajib diisi untuk item ${item.query}`, 'error');
                            return;
                        }
                    }

                    // 📦 Kalau sebelumnya draft, maka simpan akan ubah jadi final (is_draft = false)
                    const isDraftNow = this.form.is_draft == 1 ? false : this.form.is_draft;

                    const payload = {
                        ...this.form,
                        pelanggan_id: this.form.pelanggan_id ? parseInt(this.form.pelanggan_id) : null,
                        is_draft: isDraftNow ? 1 : 0,
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        // ✅ Kirim keterangan dan catatan_produksi TERPISAH (backend yang gabung)
                        items: this.form.items.map(i => ({
                            item_id: i.item_id,
                            gudang_id: i.gudang_id,
                            satuan_id: i.satuan_id,
                            jumlah: parseFloat(i.jumlah),
                            harga: parseFloat(i.harga),
                            total: parseFloat(i.jumlah) * parseFloat(i.harga),
                            keterangan: i.keterangan || '', // ✅ Terpisah
                            catatan_produksi: i.catatan_produksi || '' // ✅ Terpisah
                        }))
                    };

                    try {
                        const res = await fetch(`/penjualan/${this.form.id}/update`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();
                        if (!res.ok) throw new Error(result.message || 'Gagal update');

                        this.showToast('Perubahan disimpan.');
                        this.savedPenjualanId = this.form.id;
                        this.showPrintModal = true;
                        this.showToast('Transaksi berhasil disimpan dan status diubah menjadi final.');

                        this.form.is_draft = 0;
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.isDirty = false;
                    } catch (err) {
                        console.error(err);
                        this.showToast('Terjadi kesalahan saat menyimpan', 'error');
                    }
                },

                async cancelDraft() {
                    if (!confirm('Yakin ingin menghapus transaksi draft ini?')) return;
                    try {
                        const res = await fetch(`/penjualan/${this.form.id}/cancel`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        if (!res.ok) throw new Error('Gagal menghapus draft.');
                        this.showToast('Transaksi draft berhasil dihapus.', 'success');
                        window.location.href = '/penjualan';
                    } catch (err) {
                        console.error(err);
                        this.showToast('Terjadi kesalahan saat menghapus draft.', 'error');
                    }
                },

                // ✅ TAMBAHKAN function printNota (SAMA SEPERTI DI CREATE)
                async printNota(type) {
                    try {
                        const res = await fetch(`/penjualan/${this.savedPenjualanId}/print?type=${type}`);
                        if (!res.ok) throw new Error("Gagal memuat nota");

                        const html = await res.text();
                        const printWindow = window.open('', '_blank', 'width=800,height=600');

                        if (!printWindow) {
                            this.showToast("Popup diblokir, izinkan popup untuk melanjutkan.", "error");
                            return;
                        }

                        printWindow.document.write(html);
                        printWindow.document.close();

                        // ✅ Tunggu dokumen siap
                        printWindow.onload = () => {
                            setTimeout(() => {
                                printWindow.focus();
                                printWindow.print();

                                // ✅ Langsung close setelah print dialog muncul
                                printWindow.onafterprint = () => {
                                    printWindow.close();
                                    window.location.href = '/penjualan';
                                };

                                // ✅ Auto-close cepat (2 detik) - baik user print atau cancel
                                setTimeout(() => {
                                    if (!printWindow.closed) {
                                        printWindow.close();
                                    }
                                    window.location.href = '/penjualan';
                                }, 2000);

                            }, 500);
                        };

                    } catch (err) {
                        console.error(err);
                        this.showToast("Gagal mencetak nota, coba lagi.", "error");
                    }
                },

                goBack() {
                    window.location.href = '/penjualan';
                }
            }
        }
    </script>



@endsection
