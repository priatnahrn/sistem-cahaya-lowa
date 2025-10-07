@extends('layouts.app')

@section('title', 'Edit Penjualan')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div x-data="penjualanShowPage()" x-init="init()" class="space-y-6">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                {{ $penjualan->no_faktur }}
            </span>
        </div>

        {{-- Hidden Input Scanner --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        {{-- Form Utama --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4">

            {{-- Input Pelanggan --}}
            {{-- 🧍 Input Pelanggan --}}
            <div @click.away="openResults = false">
                <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>

                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" x-model="pelangganQuery"
                        @input.debounce.300ms="
                if (pelangganQuery.length >= 2) {
                    searchPelanggan();
                    openResults = true;
                } else {
                    form.pelanggan_id = null;
                    selectedPelangganLevel = null;
                    selectedPelangganNames = 'Customer';
                    form.is_walkin = true;
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
                        @focus="openResults = (pelangganQuery.length >= 2)"
                        placeholder="Cari pelanggan (ketik minimal 2 huruf) atau biarkan kosong untuk umum"
                        class="w-full pl-12 pr-10 py-2.5 rounded-lg border border-slate-300
                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                    {{-- Dropdown Hasil Pencarian --}}
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
                                        class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition border-b last:border-b-0">
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

                {{-- Badge Info Pelanggan --}}
                <div x-show="form.pelanggan_id || selectedPelangganNames === 'Customer'"
                    class="mt-2 flex items-center gap-2">
                    <i class="fa-solid fa-check-circle text-green-600"></i>
                    <span class="font-normal text-green-600 text-sm" x-text="selectedPelangganNames || 'Customer'"></span>
                    <span class="ml-1 text-xs px-2 py-0.5 rounded font-medium"
                        :class="{
                            'bg-blue-100 text-blue-700': (selectedPelangganLevel === 'retail' || !
                                selectedPelangganLevel),
                            'bg-yellow-100 text-yellow-700': selectedPelangganLevel === 'partai_kecil',
                            'bg-green-100 text-green-700': selectedPelangganLevel === 'grosir'
                        }"
                        x-text="formatLevel(selectedPelangganLevel || 'retail')">
                    </span>
                </div>

            </div>

            {{-- Mode, Faktur, Tanggal --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Mode Pengambilan</label>
                    <div class="relative">
                        <select name="mode" x-model="form.mode"
                            class="w-full px-3 py-2.5 rounded-lg border border-slate-200
                                           appearance-none pr-8 bg-white">
                            <option value="ambil">🏃 Ambil Sendiri</option>
                            <option value="antar">🚚 Antar Barang</option>
                        </select>
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Nomor Faktur</label>
                    <input type="text" x-model="form.no_faktur" readonly
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="form.tanggal"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                </div>
            </div>

            {{-- Deskripsi --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Deskripsi (opsional)</label>
                <input type="text" x-model="form.deskripsi" placeholder="Catatan tambahan..."
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
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
                            <th class="px-4 py-3 w-12 text-center">#</th>
                            <th class="px-4 py-3 w-[280px]">Item</th>
                            <th class="px-4 py-3 w-[200px]">Keterangan</th> {{-- 🆕 kolom baru --}}
                            <th class="px-4 py-3 w-[160px] text-center">Gudang</th>
                            <th class="px-4 py-3 w-28 text-center">Jumlah</th>
                            <th class="px-4 py-3 w-32 text-center">Satuan</th>
                            <th class="px-4 py-3 w-40 text-right">Harga</th>
                            <th class="px-4 py-3 w-40 text-right">Total</th>
                            <th class="px-2 py-3 w-12"></th>
                        </tr>
                    </thead>

                    <tbody class="align-middle">
                        <template x-for="(item, idx) in form.items" :key="idx">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100 transition">
                                <!-- Nomor urut -->
                                <td class="px-5 py-4 text-center font-medium align-middle" x-text="idx + 1"></td>

                                <!-- Nama Item -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="relative" x-data="{ open: false }">
                                        <i
                                            class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                        <input type="text" x-model="item.query"
                                            @input.debounce.300ms="searchItem(idx); open = true" @focus="open = true"
                                            @click.away="open = false" placeholder="Cari item..."
                                            class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm 
                                           focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                        <!-- Dropdown hasil pencarian -->
                                        <div x-show="open && item.query.length >= 2 && !item.item_id" x-cloak
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

                                <!-- Keterangan -->
                                <td class="px-5 py-4 align-middle">
                                    <input type="text" x-model="item.keterangan" placeholder="Catatan item (opsional)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-2.5 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                </td>


                                <!-- Gudang -->
                                <td class="px-5 py-4 align-middle">
                                    <div class="flex flex-col justify-center">
                                        <div class="relative w-full">
                                            <select x-model="item.gudang_id" @change="updateSatuanOptions(idx)"
                                                class="w-full border border-gray-300 rounded-lg pl-3 pr-8 py-2.5 text-sm text-slate-700 
                                               appearance-none focus:outline-none focus:ring-2 focus:ring-[#344579]/20 
                                               focus:border-[#344579] transition">
                                                <template x-for="g in getDistinctGudangs(item)" :key="g.gudang_id">
                                                    <option :value="g.gudang_id" x-text="g.nama_gudang"></option>
                                                </template>
                                            </select>
                                            <i
                                                class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                                        </div>
                                        <div class="text-center text-xs whitespace-nowrap mt-1.5"
                                            :class="item.stok > 0 ? 'text-slate-500' : 'text-rose-600 font-semibold'">
                                            Stok: <span x-text="formatStok(item.stok)"></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Jumlah -->
                                <td class="px-5 py-4 text-center align-middle">
                                    <input type="number" min="1" x-model.number="item.jumlah" @input="recalc"
                                        class="w-20 text-center border border-slate-300 rounded-lg px-2 py-2.5 
                                       focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
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
                                            @input="updateHarga(idx, $event.target.value)"
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
            </div>

            {{-- Button Tambah Item Manual --}}
            <div class="m-4">
                <button type="button" @click="addItemManual"
                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </button>
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

                <div class="flex gap-3">
                    <a href="{{ route('penjualan.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 transition">
                        Kembali
                    </a>
                    <button @click="isDirty && update()" type="button" :disabled="!isDirty"
                        class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 rounded-lg font-medium text-white
           transition shadow-sm hover:shadow-md
           bg-[#334976] hover:bg-[#2d3f6d]
           disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-save"></i>
                        <span>Simpan Perubahan</span>
                    </button>

                </div>
            </div>
        </div>

        <!-- 🧾 Modal Cetak Nota -->
        <div x-show="initialized && showPrintModal" x-cloak aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center">

            <!-- 🌫 Overlay dengan blur dan transisi halus -->
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
               border border-slate-200 transform transition-all"
                @click.away="showPrintModal = false">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] 
                    border-b border-slate-200 px-5 py-3 flex justify-between items-center">
                    <h3 class="text-base font-semibold text-[#334976] flex items-center gap-2">
                        <i class="fa-solid fa-print text-[#334976]"></i>
                        Cetak Nota
                    </h3>
                    <button @click="showPrintModal = false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-3">
                    <a :href="`/penjualan/${savedPenjualanId}/print?mode=kecil`" target="_blank"
                        class="block w-full px-4 py-2.5 rounded-lg text-white bg-[#334976] hover:bg-[#2d3f6d]
                       font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-receipt mr-2"></i> Cetak Nota Kecil
                    </a>

                    <a :href="`/penjualan/${savedPenjualanId}/print?mode=besar`" target="_blank"
                        class="block w-full px-4 py-2.5 rounded-lg text-white bg-green-600 hover:bg-green-700
                       font-medium text-center shadow-sm hover:shadow-md transition">
                        <i class="fa-solid fa-file-invoice mr-2"></i> Cetak Nota Besar
                    </a>
                </div>

                <!-- Footer -->
                <div class="flex justify-end px-6 py-4 bg-slate-50 border-t border-slate-200">
                    <button type="button" @click="showPrintModal = false"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-100 transition font-medium">
                        Tutup
                    </button>
                </div>
            </div>
        </div>


        {{-- Modal Tambah Pelanggan --}}
        <div x-show="showModalTambahPelanggan" x-cloak class="fixed inset-0 z-50 flex items-center justify-center"
            aria-modal="true" role="dialog">
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
                            <option value="partai_kecil">Partai Kecil</option>
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
        $itemsJson = \App\Models\Item::with(['gudangItems.gudang', 'gudangItems.satuan'])
            ->get()
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
                                // harga langsung ambil dari relasi satuan
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

    <script>
        function penjualanShowPage() {
            return {
                // === STATE ===
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
                    pelanggan_id: {{ (int) $penjualan->pelanggan_id }},
                    mode: {{ Js::from($penjualan->mode ?? 'ambil') }},
                    no_faktur: {{ Js::from($penjualan->no_faktur) }},
                    tanggal: {{ Js::from(optional($penjualan->tanggal)->format('Y-m-d')) }},
                    deskripsi: {{ Js::from($penjualan->deskripsi) }},
                    biaya_transport: {{ (int) $penjualan->biaya_transport }},
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
                            keterangan: {{ Js::from($it->keterangan ?? '') }},
                            gudang_id: {{ $it->gudang_id }},
                            satuan_id: {{ $it->satuan_id }},
                            jumlah: {{ $it->jumlah }},
                            harga: {{ $it->harga }},
                            stok: 0,
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
                            results: []
                        });
                    @endforeach

                    this.$nextTick(() => {
                        this.form.items.forEach((item, idx) => {
                            this.updateSatuanOptions(idx);
                            this.updateStockAndPrice(idx);
                        });
                        this.recalc();

                        // 🧩 ambil snapshot terakhir setelah semua update selesai
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.initialized = true;
                        this.watchFormChanges();
                    });

                    this.setupSmartScannerFocus();
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

                // === SCANNER ===
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

                // === BARCODE SCANNER ===
                handleBarcode(e) {
                    const kode = e.target.value.trim();
                    if (!kode) return;

                    // cari item berdasarkan kode_item
                    const found = this.allItems.find(i => i.kode_item?.toLowerCase() === kode.toLowerCase());

                    if (!found) {
                        this.showToast(`Item dengan kode "${kode}" tidak ditemukan`, 'error');
                        e.target.value = ''; // reset input
                        return;
                    }

                    // cek apakah sudah ada di list item
                    const existing = this.form.items.find(i => i.item_id === found.id);
                    if (existing) {
                        existing.jumlah = (parseFloat(existing.jumlah) || 0) + 1;
                        this.recalc();
                    } else {
                        // kalau belum ada, tambahkan
                        const firstGudang = found.gudangs?.[0] || {};
                        this.form.items.push({
                            item_id: found.id,
                            query: found.nama_item,
                            keterangan: '',
                            gudang_id: firstGudang.gudang_id || '',
                            satuan_id: firstGudang.satuan_id || '',
                            jumlah: 1,
                            harga: this.getHargaByLevel(firstGudang),
                            stok: firstGudang.stok || 0,
                            gudangs: found.gudangs || [],
                            filteredSatuans: found.gudangs?.filter(g => g.gudang_id === firstGudang.gudang_id) ||
                            [],
                            results: []
                        });
                        this.recalc();
                    }

                    // reset input scanner
                    e.target.value = '';
                    this.focusScanner();
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
                        return this.showToast('Nama pelanggan wajib diisi', 'error');
                    }

                    let saved = null;
                    let ok = false;

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

                        ok = res.ok;
                        saved = await res.json().catch(() => null); // biar gak meledak kalau JSON-nya aneh
                    } catch (err) {
                        console.error('Network error:', err);
                        this.showToast('Gagal menambahkan pelanggan baru (jaringan)', 'error');
                        return;
                    }

                    // ✅ Kalau sukses betulan
                    if (ok && saved && saved.id) {
                        this.form.pelanggan_id = saved.id;
                        this.selectedPelangganNames = saved.nama_pelanggan;
                        this.selectedPelangganLevel = saved.level;
                        this.pelangganQuery = saved.nama_pelanggan;
                        this.showModalTambahPelanggan = false;
                        this.showToast('Pelanggan baru berhasil ditambahkan ✅');
                        this.updateAllItemPrices();
                    }
                    // ❌ Kalau gak valid / gagal parse / gak ada ID
                    else {
                        console.error('Invalid response:', saved);
                        this.showToast('Gagal menambahkan pelanggan baru', 'error');
                    }
                },


                // === ITEM HANDLER ===
                getHargaByLevel(g) {
                    if (!g) return 0;
                    const level = this.selectedPelangganLevel || 'retail';
                    const mode = this.form.mode;
                    if (level === 'grosir')
                        return mode === 'ambil' ? (g.harga_partai_kecil || g.harga_retail) : (g.harga_grosir || g
                            .harga_retail);
                    if (level === 'partai_kecil')
                        return mode === 'ambil' ? (g.harga_partai_kecil || g.harga_retail) : (g.harga_grosir || g
                            .harga_retail);
                    return g.harga_retail || 0;
                },

                addItemManual() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        results: [],
                        gudang_id: '',
                        gudangs: [],
                        satuan_id: '',
                        filteredSatuans: [],
                        jumlah: 1,
                        harga: 0,
                        stok: 0
                    });
                    this.form = {
                        ...this.form
                    }; // trigger watcher
                },

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
                    this.form = {
                        ...this.form
                    };
                    this.recalc();
                },

                updateHarga(idx, val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.items[idx].harga = parseInt(clean) || 0;
                    this.form = {
                        ...this.form
                    };
                    this.recalc();
                },

                updateTransport(val) {
                    const clean = val.replace(/[^0-9]/g, '');
                    this.form.biaya_transport = parseInt(clean) || 0;
                    this.form = {
                        ...this.form
                    };
                    this.recalc();
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.form = {
                        ...this.form
                    };
                    this.recalc();
                },

                // === PERHITUNGAN ===
                recalc() {
                    this.$nextTick(() => {
                        let subtotal = 0;
                        this.form.items.forEach(i => {
                            const jumlah = parseFloat(i.jumlah) || 0;
                            const harga = parseFloat(i.harga) || 0;
                            subtotal += jumlah * harga;
                        });
                        this.subTotal = subtotal;
                        const transport = this.form.mode === 'antar' ? (this.form.biaya_transport || 0) : 0;
                        this.totalPembayaran = subtotal + transport;
                    });
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

                async saveOrUpdate(isDraft) {
                    if (!this.form.items.length)
                        return this.showToast('Minimal harus ada 1 item', 'error');

                    const payload = {
                        ...this.form,
                        pelanggan_id: this.form.pelanggan_id ? parseInt(this.form.pelanggan_id) : null,
                        is_draft: isDraft,
                        sub_total: this.subTotal,
                        total: this.totalPembayaran,
                        items: this.form.items.map(i => ({
                            item_id: i.item_id,
                            gudang_id: i.gudang_id,
                            satuan_id: i.satuan_id,
                            jumlah: parseFloat(i.jumlah),
                            harga: parseFloat(i.harga),
                            total: parseFloat(i.jumlah) * parseFloat(i.harga),
                            keterangan: i.keterangan || ''
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

                        // 🧩 reset snapshot dan dirty state
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.isDirty = false;
                    } catch (err) {
                        console.error(err);
                        this.showToast('Terjadi kesalahan saat menyimpan', 'error');
                    }
                }
            }
        }
    </script>


@endsection
