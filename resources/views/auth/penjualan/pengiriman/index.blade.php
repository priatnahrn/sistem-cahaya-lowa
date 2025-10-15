@extends('layouts.app')

@section('title', 'Daftar Pengiriman')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- TOAST (session-only, simple & safe) --}}
    <div x-cloak class="fixed top-6 right-6 space-y-3 z-50 w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.300ms x-init="setTimeout(() => {
                show = false;
                setTimeout(() => $el.remove(), 350);
            }, 4000)"
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
                <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
                <button class="ml-auto" @click="show=false; setTimeout(()=> $el.remove(), 350)"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.300ms x-init="setTimeout(() => {
                show = false;
                setTimeout(() => $el.remove(), 350);
            }, 4000)"
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#FFEAE6; border-color:#FCA5A5; color:#B91C1C;">
                <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Gagal</div>
                    <div>{{ session('error') }}</div>
                </div>
                <button class="ml-auto" @click="show=false; setTimeout(()=> $el.remove(), 350)"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>



    <div x-data="pengirimanPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">

            <!-- LEFT: Barcode input (ujung kiri) -->
            <div class="flex items-center gap-2 order-first">
                <div class="relative">
                    <input type="text" x-model="kodeNota" @keydown.enter.prevent="handleBarcodeScan(kodeNota)"
                        placeholder="Masukkan No. Nota"
                        class="w-56 pl-3 pr-10 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
               focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                    <!-- icon receipt di ujung kanan -->
                    <i
                        class="fa-solid fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- RIGHT: Search & Filter --}}
            <div class="flex items-center gap-3">
                <div class="relative">
                    <!-- icon pencarian di kanan -->
                    <i
                        class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                    <input type="text" placeholder="Cari" x-model="q"
                        class="w-72 pl-3 pr-10 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                      focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#2e3e6a] hover:text-white"
                    :class="{ 'bg-[#344579] text-white': hasActiveFilters() }">
                    <i class="fa-solid fa-sliders"></i>
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>


        {{-- FILTER PANEL --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Filter No Faktur --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No. Nota</label>
                    <input type="text" placeholder="Cari No. Nota" x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Tanggal --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="filters.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Nama Pelanggan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <input type="text" placeholder="Cari Pelanggan" x-model="filters.pelanggan"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Pengiriman</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Pilih Status</option>
                        <option value="Perlu Dikirim">Perlu Dikirim</option>
                        <option value="Dalam Pengiriman">Dalam Pengiriman</option>
                        <option value="Diterima">Diterima</option>
                    </select>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> pengiriman
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-white hover:bg-red-600 bg-red-400">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-visible">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-center text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_faktur')">
                                No. Faktur
                                <i class="fa-solid" :class="sortIcon('no_faktur')"></i>
                            </th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Status Pengiriman</th>
                            <th class="px-4 py-3">Supir</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="text-center hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3" x-text="fmtTanggal(r.tanggal)"></td>
                                <td class="px-4 py-3 text-green-600" x-text="r.pelanggan"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeKirim(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotKirim(r.status)"></span>
                                        <span x-text="r.status"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3" x-text="r.supir ? capitalizeName(r.supir) : '-'"></td>
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" data-dropdown-open-button @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100 focus:outline-none transition">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>

                            </tr>
                        </template>
                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="6" class="px-4 py-6">Tidak ada data pengiriman.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4 relative z-10">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] cursor-pointer"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>
                    <button @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>
        <!-- MODAL ANTAR BARANG -->
        <div x-cloak x-show="showAntarModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center min-h-screen">
            <div class="absolute inset-0 bg-black/40 " @click="showAntarModal=false"></div>

            <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-[#344579]">Detail Pengiriman</h3>
                    <button @click="showAntarModal=false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 text-sm text-slate-700">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                        <div class="flex justify-between text-sm"><span class="font-medium">No. Nota :</span> <span
                                x-text="scanData?.no_faktur || '-'"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Tanggal :</span>
                            <span x-text="fmtTanggal(scanData?.tanggal)"></span>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-1">
                        <div class="flex justify-between text-sm"><span class="font-medium">Pelanggan :</span> <span
                                x-text="scanData?.pelanggan || '-'"></span>
                        </div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Telepon :</span> <span
                                x-text="scanData?.telepon || '-'"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Alamat :</span> <span
                                x-text="scanData?.alamat || '-'"></span></div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Total Tagihan :</span>
                            <span class="font-semibold text-slate-800" x-text="formatRupiah(scanData?.total || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Sudah Dibayar :</span>
                            <span class="font-semibold text-green-700"
                                x-text="formatRupiah(scanData?.dibayar || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                            <span class="font-medium text-slate-700">Sisa Tagihan :</span>
                            <span class="font-bold text-rose-600" x-text="formatRupiah(scanData?.sisa || 0)"></span>
                        </div>
                        <template x-if="Number(scanData?.sisa) === 0">
                            <div class="text-center">
                                <span
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-green-50 text-green-700 font-semibold text-lg">
                                    <i class="fa-solid fa-circle-check text-lg"></i>
                                    LUNAS
                                </span>
                            </div>
                        </template>


                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Supir</label>
                        <input type="text" x-model="supir" placeholder="Nama Supir"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <button @click="showAntarModal=false"
                        class="w-[40%] px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition">
                        Kembali
                    </button>
                    <button @click="kirimBarang()"
                        class="w-[60%] px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] shadow transition">
                        Antar
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL TERIMA BARANG -->
        <div x-cloak x-show="showTerimaModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 min-h-screen " @click="showTerimaModal=false"></div>

            <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-[#344579]">Konfirmasi Penerimaan</h3>
                    <button @click="showTerimaModal=false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4 text-sm text-slate-700">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                        <div class="flex justify-between text-sm"><span class="font-medium">No Nota:</span> <span
                                x-text="scanData?.no_faktur || scanData?.no_pengiriman || '-'"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Tanggal:</span> <span
                                x-text="scanData?.tanggal || scanData?.tanggal_pengiriman || '-'"></span></div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-1">
                        <div class="flex justify-between text-sm"><span class="font-medium">Pelanggan:</span> <span
                                x-text="scanData?.pelanggan || '-'"></span>
                        </div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Telepon:</span> <span
                                x-text="scanData?.telepon || '-'"></span></div>
                        <div class="flex justify-between text-sm"><span class="font-medium">Alamat:</span> <span
                                x-text="scanData?.alamat || '-'"></span></div>
                    </div>

                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Total Tagihan:</span>
                            <span class="font-semibold text-slate-800" x-text="formatRupiah(scanData?.total || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Sudah Dibayar:</span>
                            <span class="font-semibold text-green-700"
                                x-text="formatRupiah(scanData?.dibayar || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                            <span class="font-medium text-slate-700">Sisa Tagihan:</span>
                            <span class="font-bold text-rose-600" x-text="formatRupiah(scanData?.sisa || 0)"></span>
                        </div>
                        <template x-if="Number(scanData?.sisa) === 0">
                            <div class="text-center">
                                <span
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-full bg-green-50 text-green-700 font-semibold text-lg">
                                    <i class="fa-solid fa-circle-check text-lg"></i>
                                    LUNAS
                                </span>
                            </div>
                        </template>

                    </div>

                    <div class="text-slate-600 text-sm">
                        Pastikan barang diterima oleh pelanggan. Tekan <strong>Terima</strong> untuk menandai sebagai
                        <em>Diterima</em>.
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <button @click="showTerimaModal=false"
                        class="w-[40%] px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition">
                        Kembali
                    </button>
                    <button @click="terimaBarang()"
                        class="w-[60%] px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700 shadow transition">
                        Terima
                    </button>
                </div>
            </div>
        </div>

        <!-- Floating dropdown portal (sama seperti penjualan) -->
        <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown" data-dropdown
            class="absolute w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
            :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">
            <button @click="window.location = dropdownData.url"
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-eye text-blue-500"></i> Detail
            </button>

            {{-- <button @click="confirmDelete(dropdownData)"
                class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600">
                <i class="fa-solid fa-trash"></i> Hapus
            </button> --}}
        </div>

        {{-- 🗑️ DELETE MODAL (Modern Design - sama seperti Penjualan) --}}
        {{-- <div x-cloak x-show="showDeleteModal" aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen">

            <!-- 🌫 Overlay -->
            <div x-show="showDeleteModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all" @click="closeDelete()"></div>

            <!-- 💎 Modal Card -->
            <div x-show="showDeleteModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-3"
                class="relative bg-white/95  w-[480px]
               rounded-2xl 
               border border-red-100 transform transition-all overflow-hidden"
                @click.away="closeDelete()">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-red-50 to-rose-50 
            border-b border-red-100 px-5 py-4 flex items-center justify-between rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                        </div>
                        <h3 class="text-base font-semibold text-red-700">
                            Konfirmasi Hapus
                        </h3>
                    </div>
                    <button @click="closeDelete()" class="text-red-400 hover:text-red-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 bg-white">
                    <p class="text-slate-700 leading-relaxed">
                        Apakah Anda yakin ingin menghapus pengiriman
                        <span class="font-semibold text-slate-900 bg-slate-100 px-2 py-0.5 rounded"
                            x-text="deleteItem.no_faktur"></span>
                        untuk pelanggan
                        <span class="font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded"
                            x-text="deleteItem.pelanggan"></span>?
                    </p>

                    <!-- Warning Box -->
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-info-circle text-amber-600 mt-0.5"></i>
                        <div class="text-sm text-amber-700">
                            <p class="font-medium">Perhatian:</p>
                            <p class="mt-1">Tindakan ini akan menghapus data pengiriman secara permanen. Proses ini
                                <strong>tidak dapat dibatalkan</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 rounded-b-2xl">
                    <button type="button" @click="closeDelete()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 
                hover:bg-white hover:border-slate-400 transition-all font-medium">
                        <i class="fa-solid fa-xmark mr-1.5"></i> Batal
                    </button>
                    <button type="button" @click="doDelete()"
                        class="px-5 py-2.5 rounded-lg bg-red-600 text-white hover:bg-red-700 
                transition-all shadow-sm hover:shadow-md font-medium group">
                        <i class="fa-solid fa-trash mr-1.5 group-hover:scale-110 transition-transform"></i>
                        Hapus
                    </button>
                </div>
            </div>
        </div> --}}


    </div>


    @php
        $pengirimanJson = $pengirimans;
    @endphp


    <script>
        function pengirimanPage() {
            return {
                data: @json($pengirimanJson),
                q: '',
                filters: {
                    no_faktur: '',
                    pelanggan: '',
                    status: '',
                    tanggal: ''
                },
                pageSize: 10,
                currentPage: 1,
                maxPageButtons: 7,
                showFilter: false,
                openActionId: null,
                showDeleteModal: false,
                deleteItem: {},

                // ==== state scan (copy dari pembayaran) ====
                scanning: false,
                kodeNota: '',
                penjualanData: null,

                // ==== Modal antar ====
                showAntarModal: false,
                scanData: null,
                supir: '',

                // ==== Modal terima ====
                showTerimaModal: false,

                // --- floating dropdown state (sama seperti penjualan) ---
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                _outsideClickHandler: null,
                _resizeHandler: null,



                init() {
                    // listener keyboard-based barcode (copy dari pembayaran)
                    let buffer = '';
                    let lastTime = Date.now();

                    window.addEventListener('keydown', (e) => {
                        const now = Date.now();

                        // jika jeda antar input terlalu lama, reset buffer
                        if (now - lastTime > 200) buffer = ''; // 200ms toleransi

                        // Enter -> proses hasil scan jika buffer panjang
                        if (e.key === 'Enter' && buffer.length > 5) {
                            this.handleBarcodeScan(buffer.trim());
                            buffer = '';
                        } else if (e.key.length === 1) {
                            buffer += e.key;
                        }

                        lastTime = now;
                    });

                    // optional: debug
                    console.log('📦 Data Pengiriman:', this.data);
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n || 0);
                },

                // --- DROPDOWN FLOATING FIX (copas dari penjualan) ---
                openDropdown(row, event) {
                    // toggle behavior: jika sama, close
                    if (this.openActionId === row.id) {
                        this.closeDropdown();
                        return;
                    }

                    this.openActionId = row.id;
                    this.dropdownData = row;

                    const rect = event.currentTarget.getBoundingClientRect();
                    const dropdownHeight = 120;

                    // hitung ruang relatif ke viewport untuk menentukan di atas/di bawah
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    // konversi koordinat viewport -> dokumen
                    const docTopBelow = rect.bottom + window.scrollY + 6; // posisi jika ditaruh di bawah
                    const docTopAbove = rect.top + window.scrollY - dropdownHeight - 6; // posisi jika ditaruh di atas
                    const docLeft = rect.right + window.scrollX - 176;

                    if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                        // tempatkan di atas (menggunakan koordinat dokumen)
                        this.dropdownPos = {
                            top: docTopAbove + 'px',
                            left: docLeft + 'px'
                        };
                    } else {
                        // tempatkan di bawah (menggunakan koordinat dokumen)
                        this.dropdownPos = {
                            top: docTopBelow + 'px',
                            left: docLeft + 'px'
                        };
                    }

                    this.dropdownVisible = true;

                    // tambahkan listener setelah satu tick agar klik pembuka tidak langsung menutupnya
                    this._outsideClickHandler = this.handleOutsideClick.bind(this);
                    setTimeout(() => {
                        document.addEventListener('click', this._outsideClickHandler);
                    }, 0);

                    // juga tutup saat resize atau scroll (opsional)
                    this._resizeHandler = this.closeDropdown.bind(this);
                    window.addEventListener('resize', this._resizeHandler);
                },

                handleOutsideClick(e) {
                    const dropdownEl = document.querySelector('[data-dropdown]');
                    const isInsideDropdown = dropdownEl && dropdownEl.contains(e.target);
                    const isTriggerButton = !!e.target.closest('[data-dropdown-open-button]');

                    // jika klik bukan di dropdown dan bukan di tombol pemicu → tutup
                    if (!isInsideDropdown && !isTriggerButton) {
                        this.closeDropdown();
                    }
                },

                closeDropdown() {
                    this.dropdownVisible = false;
                    this.openActionId = null;
                    this.dropdownData = {};

                    if (this._outsideClickHandler) {
                        document.removeEventListener('click', this._outsideClickHandler);
                        this._outsideClickHandler = null;
                    }

                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                        this._resizeHandler = null;
                    }
                },


                // helper untuk kapital tiap kata
                capitalizeName(name) {
                    if (!name) return '-';
                    return String(name)
                        .split(/\s+/)
                        .filter(Boolean)
                        .map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase())
                        .join(' ');
                },

                startScan() {
                    this.scanning = true;
                    // simulasi hasil barcode (sama seperti di pembayaran)
                    setTimeout(() => {
                        this.kodeNota = 'PJ-20251008-0001';
                        this.cariPenjualan();
                    }, 800);
                },

                async handleBarcodeScan(kode) {
                    try {
                        console.log('🔍 Deteksi hasil scan:', kode);
                        const res = await fetch(`{{ route('pengiriman.search') }}?kode=${kode}`);
                        if (!res.ok) throw new Error('Penjualan tidak ditemukan.');

                        const data = await res.json();
                        console.log('📦 Data hasil scan (penjualan):', data);

                        // simpan data scan & prefill supir jika ada
                        this.scanData = data;
                        this.supir = data.supir || '';

                        // normalisasi status (tahan beberapa variasi)
                        const stRaw = (data.status_pengiriman || data.status || '').toString().toLowerCase();

                        // jika status menunjukkan perlu dikirim / perlu diantar
                        if (stRaw === 'perlu_dikirim' || stRaw.includes('perlu')) {
                            this.showAntarModal = true;
                            this.showTerimaModal = false;
                        }
                        // jika sudah dalam pengiriman → tampilkan modal terima
                        else if (stRaw === 'dalam_pengiriman' || stRaw.includes('dalam')) {
                            this.showTerimaModal = true;
                            this.showAntarModal = false;
                        } else {
                            // fallback: tampilkan modal antar jika tidak jelas, atau pakai alert
                            // pilihan: tampilkan modal info sederhana
                            this.showNotification('info', 'Status pengiriman: ' + (data.status_pengiriman || data
                                .status || 'Unknown'));
                        }

                        this.scanning = false;
                    } catch (e) {
                        console.error('❌ Error scan:', e);
                        this.showNotification('error', e.message || 'Gagal memproses scan.');
                        this.scanning = false;
                    }
                },

                async terimaBarang() {
                    const pengirimanId = this.scanData?.pengiriman_id;
                    if (!pengirimanId) {
                        this.showNotification('error', 'Data pengiriman tidak ditemukan.');
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').content || '';

                        const res = await fetch(`/pengiriman/${pengirimanId}/update`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                status: 'diterima'
                            })
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            throw new Error(err.message || 'Gagal mengupdate status penerimaan');
                        }

                        const result = await res.json();

                        // update UI tanpa refresh
                        const idx = this.data.findIndex(d => d.id === pengirimanId);
                        if (idx !== -1) {
                            this.data[idx].status = 'Diterima';
                            // supir tetap ditampilkan jika sudah ada
                            if (this.data[idx].supir) {
                                this.data[idx].supir = this.capitalizeName(this.data[idx].supir);
                            }
                        }

                        this.showTerimaModal = false;
                        this.scanData = null;

                        this.showNotification('success', result.message || 'Status diterima');
                    } catch (e) {
                        console.error(e);
                        this.showNotification('error', e.message || 'Terjadi kesalahan saat mengupdate status.');
                    }
                },

                async cariPenjualan() {
                    if (!this.kodeNota) {
                        this.showNotification('error', 'Masukkan kode faktur terlebih dahulu!');
                        this.scanning = false;
                        return;
                    }

                    try {
                        const routePenjualanSearch = "{{ route('penjualan.search') }}";
                        const res = await fetch(`${routePenjualanSearch}?kode=${this.kodeNota}`);

                        if (!res.ok) throw new Error('Penjualan tidak ditemukan.');

                        const data = await res.json();
                        this.penjualanData = data;

                        const noFaktur = data.no_faktur || data.noNota || data.kode || this.kodeNota;
                        const found = this.data.find(r => r.no_faktur === noFaktur);

                        if (found) {
                            window.location = found.url;
                        } else {
                            this.showNotification('error', `Pengiriman untuk faktur "${noFaktur}" tidak ditemukan.`);
                        }

                        this.scanning = false;
                        this.kodeNota = '';
                    } catch (e) {
                        this.showNotification('error', e.message || 'Gagal mencari penjualan.');
                        this.scanning = false;
                    }
                },

                async kirimBarang() {
                    if (!this.supir) {
                        this.showNotification('error', 'Masukkan nama supir terlebih dahulu!');
                        return;
                    }

                    const pengirimanId = this.scanData?.pengiriman_id;
                    if (!pengirimanId) {
                        this.showNotification('error', 'Data pengiriman tidak ditemukan.');
                        return;
                    }

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').content || '';

                        const res = await fetch(`/pengiriman/${pengirimanId}/update`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                supir: this.supir,
                                status: 'dalam_pengiriman'
                            })
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            throw new Error(err.message || 'Gagal mengupdate pengiriman');
                        }

                        const result = await res.json();

                        // update UI tanpa refresh
                        const idx = this.data.findIndex(d => d.id === pengirimanId);
                        if (idx !== -1) {
                            this.data[idx].status = 'Dalam Pengiriman'; // tampilan ramah
                            this.data[idx].supir = this.capitalizeName(this.supir); // set supir terkapital
                        }

                        // tutup modal dan bersihkan
                        this.showAntarModal = false;
                        this.supir = '';
                        this.scanData = null;

                        this.showNotification('success', result.message || 'Pengiriman berhasil diperbarui!');
                    } catch (e) {
                        console.error(e);
                        this.showNotification('error', e.message || 'Terjadi kesalahan saat mengupdate pengiriman.');
                    }
                },


                // --- SORT ---
                toggleSort(field) {
                    if (this.sortBy === field) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortDir = 'asc';
                    }
                    this.currentPage = 1;
                },

                filteredList() {
                    const q = this.q.trim().toLowerCase();
                    return this.data.filter(r => {
                        if (q && !(`${r.no_faktur} ${r.pelanggan}`.toLowerCase().includes(q))) return false;
                        if (this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur
                                .toLowerCase())) return false;
                        if (this.filters.pelanggan && !r.pelanggan.toLowerCase().includes(this.filters.pelanggan
                                .toLowerCase())) return false;
                        if (this.filters.status && r.status !== this.filters.status) return false;
                        if (this.filters.tanggal) {
                            const tglRow = r.tanggal ? r.tanggal.substring(0, 10) : '';
                            if (tglRow !== this.filters.tanggal) return false;
                        }
                        return true;
                    });
                },
                filteredTotal() {
                    return this.filteredList().length
                },
                totalPages() {
                    return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize))
                },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },
                goToPage(n) {
                    this.currentPage = Math.min(Math.max(1, n), this.totalPages())
                },
                prev() {
                    if (this.currentPage > 1) this.currentPage--
                },
                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++
                },
                pagesToShow() {
                    const total = this.totalPages(),
                        max = this.maxPageButtons,
                        cur = this.currentPage;
                    if (total <= max) return Array.from({
                        length: total
                    }, (_, i) => i + 1);
                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, cur - side),
                        right = Math.min(total - 1, cur + side);
                    const pages = [1];
                    if (left > 2) pages.push('...');
                    for (let i = left; i <= right; i++) pages.push(i);
                    if (right < total - 1) pages.push('...');
                    pages.push(total);
                    return pages;
                },

                hasActiveFilters() {
                    return this.filters.no_faktur || this.filters.pelanggan || this.filters.status || this.filters.tanggal
                },
                activeFiltersCount() {
                    return ['no_faktur', 'pelanggan', 'status', 'tanggal'].filter(f => this.filters[f]).length;
                },
                resetFilters() {
                    this.filters = {
                        no_faktur: '',
                        pelanggan: '',
                        status: '',
                        tanggal: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                fmtTanggal(t) {
                    return t ? t : '-';
                },


                badgeKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'Diterima') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'Dibatalkan') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },
                dotKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-500';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-500';
                    if (st === 'Diterima') return 'bg-green-500';
                    if (st === 'Dibatalkan') return 'bg-rose-500';
                    return 'bg-slate-500';
                },

                toggleActions(id) {
                    this.openActionId = (this.openActionId === id) ? null : id
                },
                confirmDelete(item) {
                    if (!item) return;

                    // 🛑 Cek status dulu sebelum buka modal hapus
                    const status = (item.status || '').toLowerCase();

                    if (status === 'dalam pengiriman' || status === 'dalam_pengiriman' || status === 'diterima') {
                        this.showNotification(
                            'error',
                            'Pengiriman yang sedang dalam pengiriman atau sudah diterima tidak dapat dihapus.'
                        );
                        this.closeDropdown();
                        return; // ⛔ stop di sini
                    }

                    // ✅ kalau aman → baru tampilkan modal hapus
                    this.closeDropdown();
                    this.deleteItem = {
                        ...item
                    };
                    this.showDeleteModal = true;
                },

                closeDelete() {
                    this.showDeleteModal = false;
                    this.deleteItem = {}
                },
                async doDelete() {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const url = `{{ url('pengiriman') }}/${this.deleteItem.id}/delete`;

                    // 🛑 Cek status dulu sebelum hapus
                    const status = (this.deleteItem.status || '').toLowerCase();
                    if (status === 'dalam pengiriman' || status === 'dalam_pengiriman' || status === 'diterima') {
                        this.showNotification(
                            'error',
                            'Pengiriman yang sedang dalam pengiriman atau sudah diterima tidak dapat dihapus.'
                        );
                        this.closeDropdown();
                        return;
                    }

                    try {
                        const res = await fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            }
                        });

                        const result = await res.json().catch(() => ({}));

                        if (res.ok && result.success) {
                            // ✅ sukses hapus
                            const idx = this.data.findIndex(d => d.id === this.deleteItem.id);
                            if (idx !== -1) this.data.splice(idx, 1);
                            this.showNotification('success', result.message || 'Data pengiriman berhasil dihapus');
                        } else {
                            this.showNotification('error', result.message || 'Gagal menghapus data');
                        }
                    } catch (e) {
                        console.error(e);
                        this.showNotification('error', 'Terjadi kesalahan koneksi');
                    } finally {
                        this.closeDelete();
                        if (this.currentPage > this.totalPages()) this.currentPage = this.totalPages();
                    }
                },


                // --- TOAST / NOTIF ---
                showNotification(type, message, opts = {}) {
                    const dur = Number(opts.duration ?? 4000);

                    // jika ada pushToast global (versi Alpine dinamis) -> gunakan itu
                    if (window.pushToast) {
                        const t = (type === 'error') ? 'error' : (type === 'info' ? 'info' : 'success');
                        window.pushToast(t, message, {
                            duration: dur,
                            ...opts
                        });
                        return;
                    }

                    // fallback: buat root toast jika belum ada
                    const ROOT_ID = '__toast_root_custom';
                    let root = document.getElementById(ROOT_ID);
                    if (!root) {
                        root = document.createElement('div');
                        root.id = ROOT_ID;
                        // gunakan kelas Tailwind-like agar matching dengan UI
                        root.className = 'fixed top-6 right-6 z-50 flex flex-col gap-3 w-80';
                        document.body.appendChild(root);
                    }

                    // buat elemen toast
                    const id = 't_' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
                    const el = document.createElement('div');
                    el.setAttribute('data-toast-id', id);
                    el.className = 'flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm';
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(-6px)';
                    el.style.transition = 'opacity 0.28s ease, transform 0.28s ease';
                    el.style.cursor = 'default';

                    // warna berdasarkan type
                    if (type === 'error') {
                        el.style.backgroundColor = '#FFEAE6';
                        el.style.borderColor = '#FCA5A5';
                        el.style.color = '#B91C1C';
                    } else if (type === 'success') {
                        el.style.backgroundColor = '#ECFDF5';
                        el.style.borderColor = '#A7F3D0';
                        el.style.color = '#065F46';
                    } else {
                        // info / default
                        el.style.backgroundColor = '#EFF6FF';
                        el.style.borderColor = '#BFDBFE';
                        el.style.color = '#1E3A8A';
                    }

                    // isi toast (icon + teks + close)
                    const icon = document.createElement('i');
                    icon.className = type === 'error' ? 'fa-solid fa-circle-xmark text-lg mt-0.5' :
                        type === 'success' ? 'fa-solid fa-circle-check text-lg mt-0.5' :
                        'fa-solid fa-circle-info text-lg mt-0.5';
                    el.appendChild(icon);

                    const content = document.createElement('div');
                    const title = document.createElement('div');
                    title.className = 'font-semibold';
                    title.style.marginBottom = '2px';
                    title.textContent = type === 'error' ? 'Gagal' : (type === 'success' ? 'Berhasil' : 'Info');
                    const msg = document.createElement('div');
                    msg.textContent = message || '';
                    content.appendChild(title);
                    content.appendChild(msg);
                    el.appendChild(content);

                    const btn = document.createElement('button');
                    btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
                    btn.style.marginLeft = 'auto';
                    btn.style.background = 'transparent';
                    btn.style.border = 'none';
                    btn.style.cursor = 'pointer';
                    btn.style.color = 'inherit';
                    btn.addEventListener('click', () => {
                        // hide + remove
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-6px)';
                        setTimeout(() => el.remove(), 320);
                    });
                    el.appendChild(btn);

                    // append dan tampilkan dengan animation
                    root.appendChild(el);
                    // beri sedikit delay agar transition bekerja
                    requestAnimationFrame(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    });

                    // auto-hide setelah durasi -> sembunyikan lalu hapus setelah transition
                    setTimeout(() => {
                        el.style.opacity = '0';
                        el.style.transform = 'translateY(-6px)';
                        setTimeout(() => {
                            if (el.parentNode) el.remove();
                        }, 320);
                    }, dur);
                },


            }
        }
    </script>
@endsection
