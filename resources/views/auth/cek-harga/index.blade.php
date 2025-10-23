@extends('layouts.app')

@section('title', 'Cek Harga Item')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- 🎯 Root Alpine Component --}}
    <div x-data="cekHargaPage()" x-init="init()" class="space-y-6">

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

        {{-- 🎯 Hidden Input untuk Scanner Barcode --}}
        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        {{-- 📦 Card Pencarian --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="space-y-4">
                {{-- Label --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Cari Item
                    </label>
                    <div class="relative" @click.away="dropdownOpen = false">
                        {{-- Input Search --}}
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="searchItems"
                            @focus="if(searchQuery.length >= 2) dropdownOpen = true"
                            placeholder="Cari item (nama, kode, atau barcode)"
                            class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300
                                   focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition">

                        {{-- Icon Search --}}
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </span>

                        {{-- Dropdown Results --}}
                        <div x-show="dropdownOpen && searchQuery.length >= 2" x-cloak x-transition
                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 
                                   rounded-lg shadow-lg max-h-60 overflow-auto text-sm">

                            {{-- Loading --}}
                            <template x-if="isLoading">
                                <div class="px-4 py-3 text-gray-500 text-center">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Mencari item...
                                </div>
                            </template>

                            {{-- Results --}}
                            <template x-if="!isLoading && results.length > 0">
                                <ul>
                                    <template x-for="item in results" :key="item.id">
                                        <li @click="selectItem(item); dropdownOpen = false"
                                            class="px-4 py-3 cursor-pointer hover:bg-blue-50 transition 
                                                   border-b border-slate-100 last:border-b-0">
                                            <div class="flex items-center gap-3">
                                                {{-- Foto Thumbnail --}}
                                                <template x-if="item.foto_path">
                                                    <img :src="'/storage/app/public/' + item.foto_path"
                                                        :alt="item.nama_item"
                                                        class="w-10 h-10 object-cover rounded border border-slate-200">
                                                </template>
                                                <template x-if="!item.foto_path">
                                                    <div
                                                        class="w-10 h-10 bg-slate-100 flex items-center justify-center 
                                                                text-slate-400 text-xs rounded border border-slate-200">
                                                        <i class="fa-solid fa-image"></i>
                                                    </div>
                                                </template>

                                                {{-- Info Item --}}
                                                <div class="flex-1">
                                                    <div class="font-medium text-slate-800" x-text="item.nama_item"></div>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <small class="text-slate-500" x-text="item.kode_item"></small>
                                                        <span class="px-2 py-0.5 rounded text-xs bg-slate-100"
                                                            x-text="item.kategori || '-'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                            </template>

                            {{-- No Results --}}
                            <template x-if="!isLoading && results.length === 0">
                                <div class="px-4 py-3 text-center text-gray-500 italic">
                                    <i class="fa-solid fa-box-open mr-1"></i>
                                    Item tidak ditemukan
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Info Scanner --}}
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <i class="fa-solid fa-lightbulb"></i>
                    <span>Gunakan barcode scanner untuk pencarian lebih cepat</span>
                </div>
            </div>
        </div>

        {{-- 📦 Detail Item --}}
        <div x-show="selectedItem" x-cloak x-transition class="bg-white border border-slate-200 rounded-xl overflow-hidden">

            {{-- Header Item --}}
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-start gap-4">
                {{-- Foto Item --}}
                <div class="flex-shrink-0">
                    <template x-if="selectedItem?.foto_path">
                        <img :src="'/storage/app/public/' + selectedItem.foto_path" :alt="selectedItem.nama_item"
                            @click="openImagePreview('/storage/app/public/' + selectedItem.foto_path, selectedItem.nama_item)"
                            class="w-20 h-20 object-cover rounded-lg border border-slate-200 cursor-pointer 
                                    hover:ring-2 hover:ring-blue-500 hover:scale-105 transition-all shadow-sm">
                    </template>
                    <template x-if="!selectedItem?.foto_path">
                        <div
                            class="w-20 h-20 bg-slate-100 flex items-center justify-center text-slate-400 
                                    text-xs rounded-lg border border-slate-200">
                            <i class="fa-solid fa-image text-2xl"></i>
                        </div>
                    </template>
                </div>

                {{-- Info Item --}}
                <div class="flex-1">
                    <h3 class="font-semibold text-slate-800 mb-1" x-text="selectedItem?.nama_item"></h3>
                    <div class="flex items-center gap-3 text-xs text-slate-600">
                        <span>
                            <i class="fa-solid fa-barcode mr-1"></i>
                            <span x-text="selectedItem?.kode_item"></span>
                        </span>
                        <span x-show="selectedItem?.barcode">
                            <i class="fa-solid fa-qrcode mr-1"></i>
                            <span x-text="selectedItem?.barcode"></span>
                        </span>
                        <span class="px-2 py-0.5 rounded bg-slate-200">
                            <span x-text="selectedItem?.kategori || '-'"></span>
                        </span>
                    </div>
                </div>

                {{-- Close Button --}}
                <button @click="selectedItem = null; searchQuery = ''; results = []"
                    class="text-slate-400 hover:text-slate-600 transition flex-shrink-0">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            {{-- Deskripsi (jika ada) --}}
            <div x-show="selectedItem?.deskripsi"
                class="px-6 py-3 bg-blue-50 border-b border-slate-200 text-sm text-slate-700">
                <i class="fa-solid fa-info-circle mr-2 text-blue-600"></i>
                <span x-text="selectedItem?.deskripsi"></span>
            </div>

            {{-- Tabel Harga & Stok --}}
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 text-left">Gudang</th>
                            <th class="px-4 py-3 text-left">Satuan</th>
                            <th class="px-4 py-3 text-center">Konversi</th>
                            <th class="px-4 py-3 text-center">Stok</th>
                            <th class="px-4 py-3 text-right">Retail</th>
                            <th class="px-4 py-3 text-right">Partai Kecil</th>
                            <th class="px-4 py-3 text-right">Grosir</th>
                        </tr>
                    </thead>
                    <tbody class="align-middle">
                        <template x-if="selectedItem && selectedItem.gudang_items && selectedItem.gudang_items.length > 0">
                            <template x-for="(gi, idx) in selectedItem.gudang_items" :key="idx">
                                <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100 transition">
                                    {{-- Gudang --}}
                                    <td class="px-4 py-4 align-middle">
                                        <div class="flex items-center gap-2">
                                            <i class="fa-solid fa-warehouse text-slate-500 text-xs"></i>
                                            <span class="font-medium" x-text="gi.nama_gudang"></span>
                                        </div>
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="px-4 py-4 align-middle">
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 rounded 
                                                     bg-blue-100 text-blue-700 font-medium text-xs">
                                            <span x-text="gi.nama_satuan"></span>
                                        </span>
                                    </td>

                                    {{-- Konversi --}}
                                    <td class="px-4 py-4 text-center align-middle">
                                        <span class="text-slate-600 text-xs">
                                            1 = <span class="font-semibold" x-text="formatNumber(gi.konversi)"></span>
                                        </span>
                                    </td>

                                    {{-- Stok --}}
                                    <td class="px-4 py-4 text-center align-middle">
                                        <div>
                                            <div class="font-bold text-slate-800"
                                                :class="gi.stok === 0 ? 'text-red-600' : ''" x-text="formatStok(gi.stok)">
                                            </div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                Total: <span x-text="formatStok(gi.total_stok)"></span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Harga Retail --}}
                                    <td class="px-4 py-4 text-right align-middle">
                                        <div class="font-semibold text-slate-800">
                                            Rp <span x-text="formatRupiah(gi.harga_retail)"></span>
                                        </div>
                                    </td>

                                    {{-- Harga Partai Kecil --}}
                                    <td class="px-4 py-4 text-right align-middle">
                                        <div class="font-semibold text-blue-700">
                                            Rp <span x-text="formatRupiah(gi.harga_partai_kecil)"></span>
                                        </div>
                                    </td>

                                    {{-- Harga Grosir --}}
                                    <td class="px-4 py-4 text-right align-middle">
                                        <div class="font-semibold text-green-700">
                                            Rp <span x-text="formatRupiah(gi.harga_grosir)"></span>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>

                        {{-- No Data --}}
                        <template
                            x-if="selectedItem && (!selectedItem.gudang_items || selectedItem.gudang_items.length === 0)">
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                                    <p class="text-sm">Tidak ada data stok untuk item ini</p>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 📝 Empty State --}}

        {{-- 🖼️ Image Preview Modal --}}
        <div x-show="showImagePreview" x-cloak @click="closeImagePreview()" @keydown.escape.window="closeImagePreview()"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/50 p-4 min-h-screen">

            <div x-show="showImagePreview" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-90" @click.stop
                class="relative bg-white rounded-2xl shadow-2xl max-w-4xl max-h-[90vh] overflow-hidden">

                {{-- Close Button --}}
                <button @click="closeImagePreview()"
                    class="absolute top-4 right-4 z-10 w-10 h-10 flex items-center justify-center rounded-full bg-black/50 text-white hover:bg-black/70 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>

                {{-- Image Container --}}
                <div class="relative">
                    <img :src="previewImage" :alt="previewAlt" class="w-full h-auto max-h-[85vh] object-contain">

                    {{-- Image Info Overlay --}}
                    <div
                        class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent p-4 text-white">
                        <p class="font-semibold text-lg" x-text="previewAlt"></p>
                        <p class="text-sm text-white/80 mt-1">Klik di luar gambar atau tekan ESC untuk menutup</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Alpine Component --}}
    <script>
        function cekHargaPage() {
            return {
                searchQuery: '',
                results: [],
                isLoading: false,
                dropdownOpen: false,
                selectedItem: null,

                // Notification
                notifMessage: '',
                notifType: '',
                showNotif: false,

                // Image Preview
                showImagePreview: false,
                previewImage: '',
                previewAlt: '',

                init() {
                    this.setupBarcodeScanner();
                },

                setupBarcodeScanner() {
                    const barcodeInput = this.$refs.barcodeInput;
                    if (!barcodeInput) return;

                    this.focusScanner();

                    window.addEventListener('click', (e) => {
                        const tag = e.target.tagName?.toLowerCase();
                        if (!['input', 'textarea', 'select', 'button'].includes(tag)) {
                            this.focusScanner();
                        }
                    });

                    document.addEventListener('focusout', () => {
                        setTimeout(() => {
                            const active = document.activeElement;
                            if (!active || !active.tagName) return;
                            const tag = active.tagName.toLowerCase();
                            if (!['input', 'textarea', 'select'].includes(tag)) {
                                this.focusScanner();
                            }
                        }, 150);
                    });
                },

                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                },

                async handleBarcode(e) {
                    const code = e.target.value.trim();
                    if (!code) return;

                    try {
                        const res = await fetch(`/cek-harga/barcode/${encodeURIComponent(code)}`);
                        if (!res.ok) {
                            this.notify(`Item dengan barcode "${code}" tidak ditemukan`, 'error');
                            e.target.value = '';
                            return;
                        }

                        const result = await res.json();
                        if (result.success) {
                            this.selectedItem = result.data;
                            this.searchQuery = result.data.nama_item;
                            this.notify(`Item "${result.data.nama_item}" ditemukan`, 'success');
                        }
                    } catch (err) {
                        console.error('Error handleBarcode:', err);
                        this.notify('Terjadi kesalahan saat memproses barcode', 'error');
                    } finally {
                        e.target.value = '';
                        setTimeout(() => this.focusScanner(), 100);
                    }
                },

                async searchItems() {
                    if (this.searchQuery.length < 2) {
                        this.results = [];
                        this.dropdownOpen = false;
                        return;
                    }

                    this.isLoading = true;
                    this.dropdownOpen = true;

                    try {
                        const res = await fetch(`/cek-harga/search?q=${encodeURIComponent(this.searchQuery)}`);
                        this.results = await res.json();
                    } catch (err) {
                        console.error('Error searchItems:', err);
                        this.results = [];
                        this.notify('Gagal mencari item', 'error');
                    } finally {
                        this.isLoading = false;
                    }
                },

                async selectItem(item) {
                    try {
                        const res = await fetch(`/cek-harga/item/${item.id}`);
                        const result = await res.json();

                        if (result.success) {
                            this.selectedItem = result.data;
                            this.searchQuery = result.data.nama_item;
                        } else {
                            this.notify('Gagal memuat detail item', 'error');
                        }
                    } catch (err) {
                        console.error('Error selectItem:', err);
                        this.notify('Terjadi kesalahan saat memuat detail', 'error');
                    }
                },

                notify(msg, type = 'info') {
                    this.notifMessage = msg;
                    this.notifType = type;
                    this.showNotif = true;
                    setTimeout(() => (this.showNotif = false), 3000);
                },

                openImagePreview(imageSrc, altText) {
                    this.previewImage = imageSrc;
                    this.previewAlt = altText;
                    this.showImagePreview = true;
                    document.body.style.overflow = 'hidden';
                },

                closeImagePreview() {
                    this.showImagePreview = false;
                    this.previewImage = '';
                    this.previewAlt = '';
                    document.body.style.overflow = '';
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

                formatNumber(val) {
                    const num = parseFloat(val) || 1;
                    return num.toString();
                }
            }
        }
    </script>
@endsection
