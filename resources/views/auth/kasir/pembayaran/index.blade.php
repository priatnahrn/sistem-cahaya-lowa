@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TOAST --}}
    <div class="fixed top-6 right-6 space-y-3 z-[9999] w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
                style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
                <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
                <button class="ml-auto" @click="show=false"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <div x-data="pembayaranPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">

            {{-- LEFT: Input Kode Faktur --}}
            <div class="flex items-center gap-2 order-first">
                <div class="relative">
                    <input type="text" x-model="kodeNota" @keydown.enter.prevent="cariPenjualan()"
                        placeholder="Masukkan Kode Faktur"
                        class="w-56 pl-3 pr-10 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
               focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                    <i
                        class="fa-solid fa-barcode absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                </div>
            </div>

            {{-- RIGHT: Search & Filter --}}
            <div class="flex items-center gap-3">
                <div class="relative">
                    <input type="text" placeholder="Cari No Transaksi, Penjualan, Kasir..." x-model="q"
                        class="w-72 pl-3 pr-10 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                    <i
                        class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
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
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4 mt-2">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Filter No Transaksi --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Transaksi</label>
                    <input type="text" placeholder="Cari No Transaksi" x-model="filters.no_transaksi"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter Penjualan --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Penjualan</label>
                    <input type="text" placeholder="Cari Penjualan" x-model="filters.penjualan"
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

                {{-- Filter Status --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Status Pembayaran</label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700 
                focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                        <option value="">Semua</option>
                        <option value="lunas">Lunas</option>
                        <option value="belum_lunas">Belum Lunas</option>
                        <option value="batal">Batal</option>
                    </select>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> pembayaran
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
        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    {{-- TABLE HEADER --}}
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_transaksi')">
                                No Transaksi
                                <i class="fa-solid" :class="sortIcon('no_transaksi')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('tanggal')">
                                Tanggal
                                <i class="fa-solid" :class="sortIcon('tanggal')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('penjualan')">
                                Pelanggan
                                <i class="fa-solid" :class="sortIcon('penjualan')"></i>
                            </th>

                            {{-- ✅ Kolom Admin - HANYA untuk super-admin --}}
                            @if (Auth::user()->hasRole('super-admin'))
                                <th class="px-4 py-3 cursor-pointer" @click="toggleSort('admin')">
                                    Admin/Kasir
                                    <i class="fa-solid" :class="sortIcon('admin')"></i>
                                </th>
                            @endif

                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total_penjualan')">
                                Total Penjualan
                                <i class="fa-solid" :class="sortIcon('total_penjualan')"></i>
                            </th>
                            <th class="px-4 py-3 text-right cursor-pointer" @click="toggleSort('total_bayar')">
                                Total Pembayaran
                                <i class="fa-solid" :class="sortIcon('total_bayar')"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('status')">
                                Status
                                <i class="fa-solid" :class="sortIcon('status')"></i>
                            </th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>

                    {{-- TABLE BODY --}}
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3" x-text="r.no_transaksi"></td>
                                <td class="px-4 py-3" x-text="fmtTanggal(r.tanggal)"></td>
                                <td
                                    class="px-4 py-3 text-green-600 font-medium whitespace-normal break-words leading-snug max-w-[220px]">
                                    <a :href="r.url" class="hover:underline hover:text-[#2e3e6a] transition block"
                                        x-text="r.penjualan"></a>
                                </td>

                                {{-- ✅ Tampilkan nama Admin - HANYA untuk super-admin --}}
                                @if (Auth::user()->hasRole('super-admin'))
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-xs">
                                                <span x-text="r.admin_initial || '?'"></span>
                                            </div>
                                            <span class="text-sm" x-text="r.admin || '-'"></span>
                                        </div>
                                    </td>
                                @endif

                                <td class="px-4 py-3 text-right font-medium" x-text="formatRupiah(r.total_penjualan)">
                                </td>
                                <td class="px-4 py-3 text-right" x-text="formatRupiah(r.total_bayar)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeClass(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                        <span x-text="statusLabel(r.status)"></span>
                                    </span>
                                </td>
                                <td class="px-2 py-3 text-right">
                                    <button type="button" @click="openDropdown(r, $event)"
                                        class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            {{-- ✅ Colspan dinamis: 9 untuk super-admin, 8 untuk Kasir --}}
                            <td colspan="{{ Auth::user()->hasRole('super-admin') ? '9' : '8' }}" class="px-4 py-6">
                                Tidak ada data pembayaran.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            {{-- PAGINATION --}}
            <div class="px-6 py-4">
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


        {{-- ✅ Floating dropdown - PERBAIKI INI --}}
        <div x-cloak x-show="dropdownVisible" x-transition.origin.top.right id="floating-dropdown"
            class="fixed w-44 bg-white border border-slate-200 rounded-lg shadow-lg z-[9999]"
            :style="`top:${dropdownPos.top}; left:${dropdownPos.left}`">

            {{-- ✅ Detail selalu tampil (tidak perlu @can) --}}
            <button @click="window.location = dropdownData.url"
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center gap-2 text-slate-700 rounded-t-lg transition">
                <i class="fa-solid fa-eye text-blue-500"></i> Detail
            </button>

            {{-- ✅ Hapus hanya muncul jika punya permission --}}
            @can('pembayaran.delete')
                <button @click="confirmDelete(dropdownData)"
                    class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 flex items-center gap-2 text-red-600 rounded-b-lg transition border-t border-slate-100">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            @endcan
        </div>

        {{-- ========================= --}}
        {{-- MODAL TAMBAH PEMBAYARAN --}}
        {{-- ========================= --}}
        <div x-cloak x-show="showTambahModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center min-h-screen">
            <div class="absolute inset-0 bg-black/40 " @click="closeTambahModal()"></div>

            <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn">
                {{-- HEADER --}}
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-[#344579]">Tambah Pembayaran</h3>
                    <button @click="closeTambahModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- KONTEN --}}
                <div class="px-6 py-5 space-y-5">

                    {{-- INFO TRANSAKSI --}}
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                        <p class="text-sm text-slate-600"><span class="font-medium">No Nota:</span>
                            <span class="text-slate-800" x-text="penjualanData?.no_faktur || '-'"></span>
                        </p>
                        <p class="text-sm text-slate-600"><span class="font-medium">Pelanggan:</span>
                            <span class="text-slate-800" x-text="penjualanData?.pelanggan || '-'"></span>
                        </p>
                        <p class="text-sm text-slate-600"><span class="font-medium">Tanggal:</span>
                            <span class="text-slate-800" x-text="fmtTanggal(penjualanData?.tanggal) || '-'"></span>
                        </p>
                    </div>

                    {{-- TOTAL --}}
                    <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Total Tagihan:</span>
                            <span class="font-semibold text-slate-800"
                                x-text="formatRupiah(penjualanData?.total || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Sudah Dibayar:</span>
                            <span class="font-semibold text-green-700"
                                x-text="formatRupiah(penjualanData?.dibayar || 0)"></span>
                        </div>
                        <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                            <span class="font-medium text-slate-700">Sisa Tagihan:</span>
                            <span class="font-bold text-rose-600" x-text="formatRupiah(penjualanData?.sisa || 0)"></span>
                        </div>
                    </div>

                    {{-- NOMINAL PEMBAYARAN --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nominal Pembayaran</label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">Rp</span>
                            <input type="text" x-model="nominalBayarDisplay" @input="handleNominalInput($event)"
                                placeholder="0" inputmode="numeric"
                                class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-right
                   focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579] focus:outline-none">
                        </div>
                    </div>

                    {{-- METODE PEMBAYARAN --}}
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Pembayaran</label>

                        {{-- PILIH METODE --}}
                        <div class="flex gap-2">
                            {{-- TUNAI --}}
                            <button type="button" @click="pilihMetode('cash')"
                                :class="metodePembayaran === 'cash'
                                    ?
                                    'bg-green-600 text-white border-green-600' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-money-bill-wave mr-2"></i> Tunai
                            </button>

                            {{-- TRANSFER BANK --}}
                            <button type="button" @click="pilihMetode('transfer')"
                                :class="metodePembayaran === 'transfer'
                                    ?
                                    'bg-[#344579] text-white border-[#344579]' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-building-columns mr-2"></i> Transfer Bank
                            </button>
                        </div>

                        {{-- PILIH BANK (GAMBAR) --}}
                        <div x-show="metodePembayaran === 'transfer'" x-transition class="flex gap-3 mt-3 justify-center">
                            <template x-for="bank in bankList" :key="bank.name">
                                <button type="button" @click="namaBank = bank.name"
                                    :class="namaBank === bank.name ?
                                        'ring-2 ring-[#344579] border-[#344579]' :
                                        'hover:ring-1 hover:ring-slate-300'"
                                    class="h-14 bg-white border border-slate-300 w-full rounded-md
                                     flex items-center justify-center transition relative overflow-hidden ">
                                    <img :src="bank.logo" :alt="bank.name" class="w-1/2 object-contain">
                                    <div x-show="namaBank === bank.name" x-transition
                                        class="absolute inset-0 bg-[#344579]/10 rounded-xl"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <button @click="closeTambahModal()"
                        class="w-[30%] px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition">
                        Batal
                    </button>
                    <button @click="simpanPembayaran()"
                        class="w-[70%] px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] shadow transition">
                        Bayar
                    </button>
                </div>
            </div>
        </div>




        {{-- ========================= --}}
        {{-- MODAL BERHASIL PEMBAYARAN --}}
        {{-- ========================= --}}
        <div x-cloak x-show="showSuccessModal" x-transition.opacity
            class="fixed inset-0 z-[99999] flex items-center justify-center min-h-screen">
            <div class="absolute inset-0 bg-black/50 " @click="closeSuccessModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">

                {{-- ✅ ANIMASI SUKSES --}}
                <div class="flex justify-center mb-4">
                    <svg viewBox="0 0 120 120" class="w-24 h-24">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-dasharray="314" stroke-dashoffset="314" class="success-circle"></circle>
                        <polyline points="40,65 55,80 85,45" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="100"
                            stroke-dashoffset="100" class="success-check"></polyline>
                    </svg>
                </div>

                <h3 class="text-2xl font-semibold text-green-700 mb-2">Pembayaran Berhasil!</h3>

                {{-- 💰 BAGIAN KEMBALIAN --}}
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3 text-green-700">
                    <p class="text-sm font-medium">Kembalian:</p>
                    <p class="text-xl font-bold transition-all duration-300" x-text="formatRupiah(kembalian ?? 0)"></p>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <a :href="printUrl" target="_blank"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Nota
                    </a>
                    <button @click="closeSuccessModal()"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </button>
                </div>
            </div>
        </div>

        {{-- ========================= --}}
        {{-- MODAL INFO SUDAH LUNAS --}}
        {{-- ========================= --}}
        <div x-cloak x-show="showLunasModal" x-transition.opacity
            class="fixed inset-0 z-[99999] flex items-center justify-center min-h-screen">
            <div class="absolute inset-0 bg-black/50 " @click="closeLunasModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">
                {{-- ✅ ANIMASI LUNAS --}}
                <div class="flex justify-center mb-4">
                    <svg viewBox="0 0 120 120" class="w-24 h-24">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-dasharray="314" stroke-dashoffset="314" class="success-circle"></circle>
                        <polyline points="40,65 55,80 85,45" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="100"
                            stroke-dashoffset="100" class="success-check"></polyline>
                    </svg>
                </div>

                <h3 class="text-2xl font-semibold text-green-700 mb-2">Pembayaran Lunas!</h3>

                <p class="text-slate-600 text-sm mb-5">
                    Transaksi dengan faktur <span class="font-semibold text-slate-800"
                        x-text="penjualanData?.no_faktur"></span>
                    sudah dilunasi.
                </p>

                <button @click="closeLunasModal()"
                    class="px-5 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition font-medium">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </button>
            </div>
        </div>


        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fadeIn {
                animation: fadeIn 0.3s ease-out;
            }

            @keyframes drawCircle {
                to {
                    stroke-dashoffset: 0;
                }
            }

            @keyframes drawCheck {
                to {
                    stroke-dashoffset: 0;
                }
            }

            .success-circle {
                animation: drawCircle 0.8s ease-out forwards;
            }

            .success-check {
                animation: drawCheck 0.5s ease-out 0.8s forwards;
            }
        </style>

    </div>

    @php
        use Carbon\Carbon;

        // ✅ Cek apakah user adalah super-admin (BUKAN admin, BUKAN Kasir)
        $user = Auth::user();

        // PILIH SALAH SATU sesuai sistem Anda:

        // Opsi 1: Jika pakai Spatie Permission
        $isSuperAdmin = $user->hasRole('super-admin');

        // Opsi 2: Jika pakai kolom 'role' string di tabel users
        // $isSuperAdmin = $user->role === 'super-admin';

        // Opsi 3: Jika pakai relasi role() ke tabel roles
        // $isSuperAdmin = $user->role && $user->role->name === 'super-admin';

        // Mapping data pembayaran
        $pembayaransJson = $pembayarans
            ->map(function ($p) use ($isSuperAdmin) {
                $tanggal = $p->tanggal ? Carbon::parse($p->tanggal)->format('Y-m-d H:i:s') : null;

                $data = [
                    'id' => $p->id,
                    'no_transaksi' => optional($p->penjualan)->no_faktur ?? '-',
                    'tanggal' => $tanggal,
                    'penjualan' => optional($p->penjualan->pelanggan)->nama_pelanggan ?? 'Customer',
                    'total_penjualan' => (float) optional($p->penjualan)->total ?? 0,
                    'total_bayar' => (float) $p->jumlah_bayar ?? 0,
                    'status' => optional($p->penjualan)->status_bayar ?? 'unpaid',
                    'url' => route('pembayaran.show', $p->id),
                ];

                // ✅ HANYA super-admin yang dapat data admin
                if ($isSuperAdmin) {
                    $creator = $p->createdBy;
                    $data['admin'] = $creator ? $creator->name : 'Tidak diketahui';
                    $data['admin_initial'] = $creator ? strtoupper(substr($creator->name, 0, 2)) : '?';
                }

                return $data;
            })
            ->values()
            ->toArray();
    @endphp

    <script>
        function pembayaranPage() {
            return {
                // ========== STATE ==========
                q: '',
                filters: {
                    no_transaksi: '',
                    penjualan: '',
                    tanggal: '',
                    status: ''
                },
                showFilter: false,
                sortBy: 'tanggal',
                sortDir: 'desc',
                pageSize: 10,
                currentPage: 1,
                openActionId: null,
                metodePembayaran: 'cash',
                namaBank: '',
                bankList: [{
                        name: 'BRI',
                        logo: '{{ '/storage/app/public/images/bri.png' }}'
                    },
                    {
                        name: 'BNI',
                        logo: '{{ '/storage/app/public/images/bni.png' }}'
                    },
                    {
                        name: 'Mandiri',
                        logo: '{{ '/storage/app/public/images/mandiri.png' }}'
                    },
                ],
                nominalBayarDisplay: '',




                // ========== DATA ==========
                data: @json($pembayaransJson),

                // ========== TAMBAH PEMBAYARAN (MODAL) ==========
                showTambahModal: false,
                scanning: false,
                kodeNota: '',
                penjualanData: null,
                nominalBayar: 0,

                showSuccessModal: false,
                printUrl: '',
                kembalian: 0,

                showLunasModal: false,
                dropdownVisible: false,
                dropdownData: {},
                dropdownPos: {
                    top: 0,
                    left: 0
                },
                _outsideClickHandler: null,
                _scrollHandler: null,
                _resizeHandler: null,




                init() {
                    let buffer = ''; // menampung hasil scan sementara
                    let lastTime = Date.now();

                    window.addEventListener('keydown', (e) => {
                        const now = Date.now();

                        // jika jeda antar input terlalu lama, reset buffer
                        if (now - lastTime > 100) buffer = '';

                        // Enter → eksekusi cari
                        if (e.key === 'Enter' && buffer.length > 5) {
                            this.handleBarcodeScan(buffer.trim());
                            buffer = '';
                        } else if (e.key.length === 1) {
                            buffer += e.key;
                        }

                        lastTime = now;
                    });

                    console.log('📦 Data Pembayaran:', @json($pembayaransJson));

                },

                pilihMetode(metode) {
                    this.metodePembayaran = metode;
                    this.namaBank = '';
                },

                metodePembayaranLabel() {
                    switch (this.metodePembayaran) {
                        case 'cash':
                            return 'Tunai';
                        case 'transfer':
                            return 'Transfer Bank';
                        case 'qris':
                            return 'QRIS';
                        case 'wallet':
                            return 'E-Wallet';
                        default:
                            return '-';
                    }
                },
                handleNominalInput(e) {
                    // Ambil hanya angka (hapus titik, huruf, spasi)
                    let value = e.target.value.replace(/\D/g, '');
                    if (!value) {
                        this.nominalBayarDisplay = '';
                        this.nominalBayar = 0;
                        return;
                    }

                    // Simpan nilai asli (tanpa format)
                    this.nominalBayar = parseInt(value);

                    // Format tampilan jadi "10.000"
                    this.nominalBayarDisplay = new Intl.NumberFormat('id-ID').format(this.nominalBayar);
                },

                openDropdown(row, event) {
                    event.stopPropagation();

                    if (this.openActionId === row.id) {
                        this.closeDropdown();
                        return;
                    }

                    this.openActionId = row.id;
                    this.dropdownData = row;

                    const rect = event.currentTarget.getBoundingClientRect();
                    const dropdownHeight = 100;

                    let top = rect.bottom + 6;
                    const spaceBelow = window.innerHeight - rect.bottom;
                    const spaceAbove = rect.top;

                    if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                        top = rect.top - dropdownHeight - 6;
                    }

                    this.dropdownPos = {
                        top: top + 'px',
                        left: rect.right - 176 + 'px'
                    };

                    this.dropdownVisible = true;

                    // event listener global
                    this._outsideClickHandler = this.handleOutsideClick.bind(this);
                    this._scrollHandler = this.closeDropdown.bind(this);
                    this._resizeHandler = this.closeDropdown.bind(this);

                    document.addEventListener('click', this._outsideClickHandler);
                    window.addEventListener('scroll', this._scrollHandler, true);
                    window.addEventListener('resize', this._resizeHandler);
                },

                handleOutsideClick(e) {
                    const dropdown = document.getElementById('floating-dropdown');
                    if (
                        dropdown &&
                        !dropdown.contains(e.target) &&
                        !e.target.closest('[x-on\\:click^="openDropdown"]')
                    ) {
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
                    if (this._scrollHandler) {
                        window.removeEventListener('scroll', this._scrollHandler, true);
                        this._scrollHandler = null;
                    }
                    if (this._resizeHandler) {
                        window.removeEventListener('resize', this._resizeHandler);
                        this._resizeHandler = null;
                    }
                },



                async handleBarcodeScan(kode) {
                    try {
                        console.log('🔍 Deteksi hasil scan:', kode);
                        const res = await fetch(`{{ route('penjualan.search') }}?kode=${kode}`);
                        if (!res.ok) throw new Error('Penjualan tidak ditemukan.');

                        const data = await res.json();
                        console.log('📦 Data hasil scan:', data);
                        this.penjualanData = data;

                        // ⚙️ Cek status bayar langsung dari data
                        if (
                            data.status_bayar && ['paid', 'lunas'].includes(data.status_bayar.toLowerCase())
                        ) {
                            // ✅ Jika sudah lunas → tampilkan modal lunas
                            this.showTambahModal = false;
                            this.showSuccessModal = false;
                            this.showLunasModal = true;
                            this.kodeNota = '';
                            console.log('✅ Faktur hasil scan sudah lunas, tampilkan modal info.');
                        } else {
                            // 💳 Jika belum lunas → tampilkan modal tambah pembayaran
                            this.showTambahModal = true;
                            console.log('🟡 Faktur hasil scan belum lunas, buka modal pembayaran.');
                        }

                    } catch (e) {
                        console.error('❌ Error scan:', e.message);
                        this.showToast(e.message, 'error');
                    }
                },



                // ========== FORMATTERS ==========
                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n || 0);
                },

                fmtTanggal(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);

                    // Jika data dari server masih UTC, tambahkan offset +8 jam (Makassar)
                    d.setHours(d.getHours() + 8);

                    const tanggal =
                        `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}`;
                    const waktu = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                    return `${tanggal} ${waktu}`;
                },


                // ========== SORTING & FILTERING ==========
                sortIcon(field) {
                    if (this.sortBy !== field) return 'fa-sort ml-2';
                    return this.sortDir === 'asc' ? 'fa-arrow-up ml-2' : 'fa-arrow-down ml-2';
                },

                toggleSort(field) {
                    if (this.sortBy === field) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortBy = field;
                        this.sortDir = 'asc';
                    }
                },

                filteredList() {
                    const q = this.q.toLowerCase();
                    let list = this.data.filter(r => {
                        // 🔍 Pencarian umum
                        if (q && !(`${r.no_transaksi} ${r.penjualan}`.toLowerCase().includes(q))) return false;

                        // 🔹 Filter No Transaksi
                        if (this.filters.no_transaksi && !r.no_transaksi.toLowerCase().includes(this.filters
                                .no_transaksi.toLowerCase()))
                            return false;

                        // 🔹 Filter Nama Pelanggan / Penjualan
                        if (this.filters.penjualan && !r.penjualan.toLowerCase().includes(this.filters.penjualan
                                .toLowerCase()))
                            return false;

                        // 🔹 Filter Status
                        if (this.filters.status && r.status !== this.filters.status)
                            return false;

                        // 🔹 Filter Tanggal (gunakan prefix cocok, contoh: "2025-10-08" cocok dengan "2025-10-08 13:45")
                        if (this.filters.tanggal && r.tanggal && !r.tanggal.startsWith(this.filters.tanggal))
                            return false;

                        return true;
                    });

                    // 🔹 Sorting (tetap sama)
                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    list.sort((a, b) => {
                        if (a[this.sortBy] > b[this.sortBy]) return dir;
                        if (a[this.sortBy] < b[this.sortBy]) return -dir;
                        return 0;
                    });

                    return list;
                },


                filteredTotal() {
                    return this.filteredList().length;
                },

                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },

                // ========== PAGINATION ==========
                totalPages() {
                    return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize));
                },
                goToPage(n) {
                    n = Math.max(1, Math.min(n, this.totalPages()));
                    this.currentPage = n;
                    this.openActionId = null;
                },
                prev() {
                    if (this.currentPage > 1) this.currentPage--;
                },
                next() {
                    if (this.currentPage < this.totalPages()) this.currentPage++;
                },
                pagesToShow() {
                    const total = this.totalPages();
                    const max = 7;
                    const current = this.currentPage;
                    if (total <= max) return Array.from({
                        length: total
                    }, (_, i) => i + 1);
                    const pages = [];
                    const side = Math.floor((max - 3) / 2);
                    const left = Math.max(2, current - side);
                    const right = Math.min(total - 1, current + side);
                    pages.push(1);
                    if (left > 2) pages.push('...');
                    for (let i = left; i <= right; i++) pages.push(i);
                    if (right < total - 1) pages.push('...');
                    pages.push(total);
                    return pages;
                },

                // ========== STATUS BADGES ==========
                badgeClass(st) {
                    if (st === 'paid') return 'bg-green-50 text-green-700 border border-green-200';
                    if (st === 'unpaid') return 'bg-amber-50 text-amber-700 border border-amber-200';
                    if (st === 'batal') return 'bg-rose-50 text-rose-700 border border-rose-200';
                    return 'bg-slate-50 text-slate-700 border border-slate-200';
                },
                dotClass(st) {
                    if (st === 'paid') return 'bg-green-500';
                    if (st === 'unpaid') return 'bg-amber-500';
                    if (st === 'batal') return 'bg-rose-500';
                    return 'bg-slate-500';
                },
                statusLabel(st) {
                    if (st === 'paid') return 'Lunas';
                    if (st === 'unpaid') return 'Belum Lunas';
                    return '-';
                },


                // ========== FILTERS ==========
                hasActiveFilters() {
                    return this.filters.no_transaksi || this.filters.penjualan || this.filters.tanggal || this.filters
                        .status;
                },
                activeFiltersCount() {
                    return Object.values(this.filters).filter(v => v).length;
                },
                resetFilters() {
                    this.filters = {
                        no_transaksi: '',
                        penjualan: '',
                        tanggal: '',
                        status: ''
                    };
                    this.q = '';
                    this.currentPage = 1;
                },

                // ========== ACTION MENU ==========
                toggleActions(id) {
                    this.openActionId = (this.openActionId === id) ? null : id;
                },

                // ========== TAMBAH PEMBAYARAN (MODAL) ==========
                openTambahModal() {
                    this.showTambahModal = true;
                    this.penjualanData = null;
                    this.kodeNota = '';
                    this.nominalBayar = 0;
                },
                closeTambahModal() {
                    this.showTambahModal = false;
                    this.scanning = false;
                    this.penjualanData = null;
                },

                closeLunasModal() {
                    this.showLunasModal = false;
                    this.penjualanData = null;
                    this.kodeNota = '';
                },


                startScan() {
                    this.scanning = true;
                    // simulasi hasil barcode (nanti diganti hasil kamera)
                    setTimeout(() => {
                        this.kodeNota = 'PJ-20251008-0001';
                        this.cariPenjualan();
                    }, 1500);
                },

                async cariPenjualan() {
                    if (!this.kodeNota) {
                        this.showToast('Masukkan kode faktur terlebih dahulu!', 'error');
                        return;
                    }

                    try {
                        const routePenjualanSearch = "{{ route('penjualan.search') }}";
                        console.log('🔍 Mencari:', this.kodeNota, '->',
                            `${routePenjualanSearch}?kode=${this.kodeNota}`);
                        const res = await fetch(`${routePenjualanSearch}?kode=${this.kodeNota}`);

                        if (!res.ok) throw new Error('Penjualan tidak ditemukan.');

                        const data = await res.json();
                        console.log('✅ Penjualan ditemukan:', data);
                        this.penjualanData = data;

                        if (
                            data.status_bayar && ['paid', 'lunas'].includes(data.status_bayar.toLowerCase())
                        ) {
                            this.showTambahModal = false;
                            this.showSuccessModal = false;
                            this.showLunasModal = true;
                            this.kodeNota = '';
                            console.log('✅ Faktur sudah lunas, tampilkan modal info.');
                        } else {
                            this.showTambahModal = true;
                            console.log('🟡 Faktur belum lunas, buka modal pembayaran.');
                        }



                        this.scanning = false;
                    } catch (e) {
                        this.showToast(e.message || 'Gagal mencari penjualan.', 'error');
                        this.scanning = false;
                    }
                },

                async simpanPembayaran() {
                    if (!this.penjualanData || this.nominalBayar <= 0) {
                        this.showToast('Masukkan kode faktur terlebih dahulu!', 'error');

                        return;
                    }

                    const payload = {
                        penjualan_id: this.penjualanData.id,
                        jumlah_bayar: this.nominalBayar,
                        sisa: this.penjualanData.sisa - this.nominalBayar,
                        method: this.metodePembayaran,
                        keterangan: this.metodePembayaran === 'transfer' && this.namaBank ?
                            `Transfer ke ${this.namaBank}` : this.metodePembayaran === 'cash' ?
                            'Pembayaran tunai' : this.metodePembayaran === 'qris' ?
                            'Pembayaran melalui QRIS' : this.metodePembayaran === 'wallet' ?
                            'Pembayaran melalui E-Wallet' : null,
                    };

                    try {
                        const res = await fetch(`/pembayaran`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) throw new Error('Gagal menyimpan pembayaran.');
                        const result = await res.json();
                        if (!result.success) throw new Error('Pembayaran gagal disimpan.');

                        // ✅ Hitung kembalian
                        const totalTagihan = this.penjualanData.sisa || this.penjualanData.total;
                        this.kembalian =
                            this.metodePembayaran === 'cash' ?
                            Math.max(0, this.nominalBayar - totalTagihan) :
                            0;

                        this.printUrl = `/pembayaran/${result.data.id}`; // link nota
                        this.closeTambahModal();
                        this.showSuccessModal = true;
                    } catch (e) {
                        this.showToast(e.message || 'Terjadi kesalahan saat menyimpan pembayaran.', 'error');
                    }

                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                    setTimeout(() => window.location.reload(), 1000);
                },



                // ========== TOAST ========== 
                showToast(message = '', type = 'success') {
                    const el = document.createElement('div');
                    el.className =
                        'flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm fixed top-6 right-6 z-[10000] w-80 animate-fadeIn';

                    // Warna berdasarkan tipe
                    if (type === 'success') {
                        el.style.backgroundColor = '#ECFDF5';
                        el.style.borderColor = '#A7F3D0';
                        el.style.color = '#065F46';
                        el.innerHTML = `
            <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
            <div>
                <div class="font-semibold">Berhasil</div>
                <div>${message}</div>
            </div>`;
                    } else if (type === 'error') {
                        el.style.backgroundColor = '#FEF2F2';
                        el.style.borderColor = '#FECACA';
                        el.style.color = '#991B1B';
                        el.innerHTML = `
            <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
            <div>
                <div class="font-semibold">Gagal</div>
                <div>${message}</div>
            </div>`;
                    } else {
                        el.style.backgroundColor = '#EFF6FF';
                        el.style.borderColor = '#BFDBFE';
                        el.style.color = '#1E3A8A';
                        el.innerHTML = `
            <i class="fa-solid fa-info-circle text-lg mt-0.5"></i>
            <div>
                <div class="font-semibold">Info</div>
                <div>${message}</div>
            </div>`;
                    }

                    document.body.appendChild(el);
                    setTimeout(() => {
                        el.classList.add('opacity-0', 'translate-x-3');
                        setTimeout(() => el.remove(), 300);
                    }, 4000);
                },

            }
        }
    </script>

@endsection
