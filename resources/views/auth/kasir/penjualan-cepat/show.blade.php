<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Penjualan Cepat</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        /* Hapus spinner number */
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .no-spinner {
            -moz-appearance: textfield;
        }

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
</head>

<body class="bg-slate-100 text-slate-700 min-h-screen flex flex-col">

    <div x-data="penjualanCepatShow()" x-init="init()" class="min-h-screen bg-slate-100">
        <!-- HEADER -->
        <header class="bg-[#344579] text-white py-4 px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan-cepat.index') }}"
                    class="bg-[#2c3e6b] hover:bg-[#24355b] px-3 py-2 rounded-md flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <h1 class="font-semibold text-lg">Detail Penjualan Cepat</h1>
            </div>

            <div class="text-right text-sm leading-tight">
                <div>No. Faktur: <span class="font-bold" x-text="form.no_faktur"></span></div>
                <div x-text="fmtDate(form.tanggal)"></div>
                <div x-show="form.is_draft == 1" class="mt-1">
                    <span class="inline-block px-2 py-0.5 rounded text-xs bg-yellow-500 text-white font-medium">
                        PENDING
                    </span>
                </div>
            </div>
        </header>

        <input type="text" x-ref="barcodeInput" @keydown.enter.prevent="handleBarcode($event)"
            class="absolute opacity-0 pointer-events-none" autocomplete="off">

        <!-- MAIN -->
        <main class="max-w-[95%] mx-auto mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6 pb-8">
            <!-- LEFT: Items (2/3) -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-slate-700">Daftar Item</h2>
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

                                    <!-- Item dengan Keterangan -->
                                    <td class="px-5 py-4 align-middle">
                                        <div class="relative">
                                            <div class="flex items-center gap-2">
                                                <!-- Tombol Keterangan -->
                                                <button type="button" @click.prevent="toggleItemNote(idx)"
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
                                                        @cannot('penjualan_cepat.update') disabled readonly @endcannot
                                                        @input.debounce.300ms="searchItem(idx)"
                                                        @focus="item.query.length >= 2 && searchItem(idx); item._dropdownOpen = true"
                                                        @click="item.query.length >= 2 ? item._dropdownOpen = true : null"
                                                        @keydown.escape="item._dropdownOpen = false"
                                                        placeholder="Cari item"
                                                        class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                                    <!-- Icon pencarian -->
                                                    <span x-show="!item.item_id" x-cloak
                                                        x-transition.opacity.duration.150ms
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                                        <i class="fa-solid fa-magnifying-glass"></i>
                                                    </span>

                                                    <!-- Dropdown hasil pencarian -->
                                                    <div x-show="item._dropdownOpen && item.query.length >= 2 && item.results && item.results.length > 0"
                                                        x-cloak x-transition
                                                        class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto text-sm">

                                                        <template x-for="r in item.results" :key="r.id">
                                                            <div @click="selectItem(idx, r); item._dropdownOpen = false"
                                                                class="px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 cursor-pointer rounded transition">
                                                                <div class="font-medium" x-text="r.nama_item"></div>
                                                                <div class="text-xs text-slate-500"
                                                                    x-text="r.kode_item"></div>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Form Keterangan -->
                                            <template x-if="item.showNote">
                                                <div class="mt-3 space-y-3"
                                                    x-transition:enter="transition ease-out duration-300"
                                                    x-transition:enter-start="opacity-0"
                                                    x-transition:enter-end="opacity-100">

                                                    <!-- Form untuk Item Spandek -->
                                                    <template x-if="item.is_spandek === true">
                                                        <div class="space-y-3">
                                                            <div>
                                                                <label
                                                                    class="block text-xs font-medium text-slate-700 mb-1.5">
                                                                    Keterangan <span class="text-red-500">*</span>
                                                                </label>
                                                                <input type="text" x-model="item.keterangan"
                                                                    placeholder="Contoh: Panjang 6m, Lebar 1m"
                                                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                                            </div>

                                                            <div>
                                                                <label
                                                                    class="block text-xs font-medium text-slate-700 mb-1.5">
                                                                    Jenis Spandek <span class="text-red-500">*</span>
                                                                </label>
                                                                <select x-model="item.catatan_produksi"
                                                                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition bg-white">
                                                                    <option value="">-- Pilih jenis spandek --
                                                                    </option>
                                                                    <option value="Spandek Biasa">Spandek Biasa</option>
                                                                    <option value="Spandek Pasir">Spandek Pasir</option>
                                                                    <option value="Spandek Laminasi">Spandek Laminasi
                                                                    </option>
                                                                    <option value="Spandek Warna">Spandek Warna
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </template>

                                                    <!-- Form untuk Item Biasa -->
                                                    <template x-if="item.is_spandek === false">
                                                        <div>
                                                            <label
                                                                class="block text-xs font-medium text-slate-700 mb-1.5">
                                                                Keterangan
                                                            </label>
                                                            <input type="text" x-model="item.keterangan"
                                                                placeholder="Catatan tambahan (opsional)"
                                                                class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />
                                                        </div>
                                                    </template>

                                                    <!-- Fallback: Item belum dipilih -->
                                                    <template
                                                        x-if="item.is_spandek === undefined || item.is_spandek === null">
                                                        <div
                                                            class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-center">
                                                            <small class="text-amber-700 text-xs">
                                                                Pilih item terlebih dahulu
                                                            </small>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- Gudang -->
                                    <td class="px-5 py-4 align-middle">
                                        <div class="relative w-full">
                                            <div
                                                class="border border-slate-300 rounded-lg px-3 pr-8 py-[6px] text-sm text-slate-700 
                                        focus-within:ring-2 focus-within:ring-[#344579]/20 focus-within:border-[#344579] transition">
                                                <div class="flex flex-col leading-tight">
                                                    <!-- Nama gudang -->
                                                    <div class="text-[13px] text-slate-700">
                                                        <span
                                                            x-text="(getDistinctGudangs(item).find(g => g.gudang_id == item.gudang_id) || getDistinctGudangs(item)[0] || {}).nama_gudang || '-'">
                                                        </span>

                                                        <!-- Select transparan -->
                                                        <select x-model="item.gudang_id"
                                                            @change="updateSatuanOptions(idx)"
                                                            @cannot('penjualan_cepat.update') disabled @endcannot
                                                            class="absolute inset-0 opacity-0 cursor-pointer">
                                                            <template x-for="g in getDistinctGudangs(item)"
                                                                :key="g.gudang_id">
                                                                <option :value="g.gudang_id" x-text="g.nama_gudang">
                                                                </option>
                                                            </template>
                                                        </select>
                                                    </div>

                                                    <!-- Stok -->
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
                                        <input type="text" :value="item.qty ? formatJumlah(item.qty) : ''"
                                            @cannot('penjualan_cepat.update') disabled readonly @endcannot
                                            @input="updateQtyFormatted(idx, $event.target.value)"
                                            class="no-spinner w-24 text-center border border-slate-300 rounded-lg px-2 py-2.5 
                                        focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                            inputmode="numeric" pattern="[0-9]*" />
                                    </td>

                                    <!-- Satuan -->
                                    <td class="px-5 py-4 align-middle">
                                        <div class="relative">
                                            <select x-model="item.satuan_id" @change="updateHarga(idx)"
                                                @cannot('penjualan_cepat.update') disabled  @endcannot
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
                                                @cannot('penjualan_cepat.update') disabled readonly @endcannot
                                                @input="
                                            const clean = $event.target.value.replace(/\D/g, '');
                                            item.harga = parseInt(clean || 0);
                                            item.manual = true;
                                            recalc();
                                        "
                                                class="pl-7 pr-2 w-full text-right border border-slate-300 rounded-lg py-2.5 
                                            focus:border-blue-500 focus:ring-1 focus:ring-blue-200" />
                                        </div>
                                    </td>

                                    <!-- Total -->
                                    <td
                                        class="px-5 py-4 text-right font-semibold text-slate-800 align-middle whitespace-nowrap">
                                        Rp <span x-text="formatRupiah((item.qty||0) * (item.harga||0))"></span>
                                    </td>

                                    <!-- Hapus -->
                                    @can('penjualan_cepat.update')
                                        <td class="px-3 py-4 text-center align-middle">
                                            <button type="button" @click="removeItem(idx)"
                                                class="text-rose-600 hover:text-rose-800 transition">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    @endcan
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <!-- Button Tambah Item Manual -->
                    @can('penjualan_cepat.update')
                        <div class="m-4">
                            <button type="button" @click="addItem"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                                <i class="fa-solid fa-plus"></i> Tambah Item Baru
                            </button>
                        </div>
                    @endcan
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

                <!-- Tombol Aksi -->
                <div class="mt-6">
                    <!-- Mode Draft (Pending) -->
                    <template x-if="form.is_draft == 1">
                        <div class="flex flex-col gap-3">
                            @can('penjualan_cepat.update')
                                <button @click="update" :disabled="!isDirty || isSaving"
                                    :class="[
                                        'w-full py-2.5 rounded-lg text-white font-medium shadow-sm transition',
                                        (!isDirty || isSaving) ?
                                        'bg-gray-200 cursor-not-allowed' :
                                        'bg-[#344579] hover:bg-[#2d3f6d] hover:shadow-md'
                                    ]">
                                    <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                                </button>

                                <button @click="cancelDraft"
                                    class="w-full py-2.5 rounded-lg border border-red-500 text-red-500 hover:bg-red-500 hover:text-white transition font-medium">
                                    Batal
                                </button>
                            @endcan
                        </div>
                    </template>

                    <!-- Mode Final (Non-Draft) -->
                    <template x-if="form.is_draft == 0">
                        <div class="flex md:flex-row flex-col gap-3 w-full">
                            <button @click="goBack"
                                class="w-[30%] py-2.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-100 transition font-medium">
                                Kembali
                            </button>

                            @can('penjualan_cepat.update')
                                <button @click="update" :disabled="!isDirty || isSaving"
                                    :class="[
                                        'w-[70%] py-2.5 rounded-lg text-white font-medium transition ',
                                        (!isDirty || isSaving) ?
                                        'bg-gray-200 cursor-not-allowed' :
                                        'bg-[#344579] hover:bg-[#2d3f6d] hover:shadow-md'
                                    ]">
                                    Simpan Perubahan
                                </button>
                            @endcan
                        </div>
                    </template>
                </div>
            </div>
        </main>

        <!-- Modal Print Nota -->
        <div x-show="showPrintModal" x-cloak aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen" style="display: none;">
            <!-- ✅ TAMBAHKAN inline style -->

            <!-- Overlay -->
            <div x-show="showPrintModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all"></div>

            <!-- Modal Card -->
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
                    <button type="button" @click="window.location.href = '/penjualan-cepat'"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 
                hover:bg-slate-100 transition font-medium">
                        Kembali
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Perubahan Total Penjualan -->
        <div x-show="showAdjustmentModal" x-cloak aria-modal="true" role="dialog"
            class="fixed inset-0 z-50 flex items-center justify-center min-h-screen" style="display: none;">

            <!-- Overlay -->
            <div x-show="showAdjustmentModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-all"></div>

            <!-- Modal Card -->
            <div x-show="showAdjustmentModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-3"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                class="relative bg-white/95 backdrop-blur-sm w-[420px]
               rounded-2xl shadow-[0_10px_35px_-5px_rgba(51,73,118,0.25)]
               border border-slate-200 transform transition-all overflow-hidden"
                @click.away="showAdjustmentModal = false">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] 
            border-b border-slate-200 px-5 py-3 flex justify-between items-center rounded-t-2xl">
                    <h3 class="text-base font-semibold text-[#334976] flex items-center gap-2">
                        <i class="fa-solid fa-exclamation-circle text-amber-600"></i>
                        <span x-text="adjustmentAmount > 0 ? 'Ada Kekurangan Pembayaran' : 'Pengembalian Dana'"></span>
                    </h3>
                    <button @click="showAdjustmentModal = false"
                        class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4 bg-white">

                    <!-- Info Perubahan Total -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-2">
                        <p class="text-sm text-slate-600 mb-3">
                            <i class="fa-solid fa-info-circle mr-1"></i>
                            Total penjualan berubah setelah Anda mengedit transaksi
                        </p>

                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Total Sebelumnya:</span>
                            <span class="font-semibold text-slate-800" x-text="'Rp ' + formatRupiah(oldTotal)"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-600">Total Sekarang:</span>
                            <span class="font-semibold text-slate-800" x-text="'Rp ' + formatRupiah(total)"></span>
                        </div>
                        <div class="border-t border-slate-300 pt-2 mt-2"></div>
                        <div class="flex justify-between text-base">
                            <span class="font-semibold"
                                x-text="adjustmentAmount > 0 ? 'Kekurangan:' : 'Kelebihan Bayar:'"></span>
                            <span class="font-bold" :class="adjustmentAmount > 0 ? 'text-red-600' : 'text-green-600'"
                                x-text="'Rp ' + formatRupiah(Math.abs(adjustmentAmount))"></span>
                        </div>
                    </div>

                    <!-- JIKA TOTAL NAIK (Ada kekurangan) -->
                    <template x-if="adjustmentAmount > 0">
                        <div class="space-y-4">
                            <!-- Pesan -->
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                                <p class="text-sm text-amber-800">
                                    <i class="fa-solid fa-wallet mr-2"></i>
                                    Pelanggan perlu membayar <strong
                                        x-text="'Rp ' + formatRupiah(adjustmentAmount)"></strong> lagi
                                </p>
                            </div>

                            <!-- Input Nominal Pembayaran Tambahan -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">
                                    Nominal Pembayaran Tambahan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm font-medium">Rp</span>
                                    <input type="text" x-model="nominalAdjustmentDisplay"
                                        @input="handleAdjustmentInput($event)" placeholder="0" inputmode="numeric"
                                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-slate-300 text-slate-700 text-right
                                          focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579] focus:outline-none">
                                </div>
                                <p class="text-xs text-slate-500 mt-1">
                                    Minimum: <span x-text="'Rp ' + formatRupiah(adjustmentAmount)"></span>
                                </p>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-slate-700">Cara Pembayaran</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="adjustmentMethod = 'cash'"
                                        :class="adjustmentMethod === 'cash' ?
                                            'bg-green-600 text-white border-green-600' :
                                            'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                        class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                        <i class="fa-solid fa-money-bill-wave mr-2"></i> Tunai
                                    </button>

                                    <button type="button" @click="adjustmentMethod = 'transfer'"
                                        :class="adjustmentMethod === 'transfer' ?
                                            'bg-[#344579] text-white border-[#344579]' :
                                            'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                        class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                        <i class="fa-solid fa-building-columns mr-2"></i> Transfer
                                    </button>
                                </div>

                                <!-- ✅ TAMBAHKAN: Pilihan Bank (muncul jika pilih Transfer) -->
                                <div x-show="adjustmentMethod === 'transfer'" x-transition
                                    class="flex gap-3 justify-center">
                                    <template x-for="bank in bankList" :key="bank.name">
                                        <button type="button" @click="adjustmentBankName = bank.name"
                                            :class="adjustmentBankName === bank.name ?
                                                'ring-2 ring-[#344579] border-[#344579]' :
                                                'hover:ring-1 hover:ring-slate-300'"
                                            class="h-14 bg-white border border-slate-300 w-full rounded-md
                 flex items-center justify-center transition relative overflow-hidden">
                                            <img :src="bank.logo" :alt="bank.name"
                                                class="w-1/2 object-contain">
                                            <div x-show="adjustmentBankName === bank.name" x-transition
                                                class="absolute inset-0 bg-[#344579]/10 rounded-xl"></div>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <!-- Kembalian (jika ada) -->
                            {{-- <div x-show="nominalAdjustment > adjustmentAmount" x-transition
                                class="bg-green-50 border border-green-200 rounded-lg p-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-green-800">Kembalian:</span>
                                    <span class="text-lg font-bold text-green-700"
                                        x-text="'Rp ' + formatRupiah(nominalAdjustment - adjustmentAmount)"></span>
                                </div>
                            </div> --}}
                        </div>
                    </template>

                    <!-- JIKA TOTAL TURUN (Ada kelebihan bayar) -->
                    <template x-if="adjustmentAmount < 0">
                        <div class="space-y-3">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fa-solid fa-hand-holding-dollar text-green-600 text-xl mt-0.5"></i>
                                    <div>
                                        <p class="font-medium text-green-800 mb-1">Pengembalian Dana ke Pelanggan</p>
                                        <p class="text-sm text-green-700">
                                            Pelanggan berhak mendapat pengembalian sebesar
                                            <strong x-text="'Rp ' + formatRupiah(Math.abs(adjustmentAmount))"></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-xs text-blue-800">
                                    <i class="fa-solid fa-info-circle mr-1"></i>
                                    Pengembalian dana akan tercatat otomatis di sistem
                                </p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-3 px-6 py-4 bg-slate-50 border-t border-slate-200">
                    <button @click="showAdjustmentModal = false"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-white transition font-medium">
                        Batal
                    </button>
                    <button @click="saveAdjustment()"
                        :disabled="adjustmentAmount > 0 && nominalAdjustment < adjustmentAmount"
                        :class="[
                            'px-5 py-2.5 rounded-lg font-medium text-white shadow transition',
                            (adjustmentAmount > 0 && nominalAdjustment < adjustmentAmount) ?
                            'bg-gray-400 cursor-not-allowed' :
                            'bg-[#344579] hover:bg-[#2e3e6a]'
                        ]">
                        <span x-text="adjustmentAmount > 0 ? 'Proses Pembayaran' : 'Proses Pengembalian'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Pembayaran Berhasil (Adjustment) -->
        <div x-show="showAdjustmentSuccessModal" x-cloak aria-modal="true" role="dialog"
            class="fixed inset-0 z-[99999] flex items-center justify-center min-h-screen" style="display: none;">

            <!-- Overlay -->
            <div x-show="showAdjustmentSuccessModal" x-transition.opacity.duration.400ms
                class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

            <!-- Modal Card -->
            <div x-show="showAdjustmentSuccessModal" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                class="relative bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">

                <!-- ✅ ANIMASI SUKSES -->
                <div class="flex justify-center mb-4">
                    <svg viewBox="0 0 120 120" class="w-24 h-24">
                        <circle cx="60" cy="60" r="50" fill="none" stroke="#34D399"
                            stroke-width="10" stroke-dasharray="314" stroke-dashoffset="314" class="success-circle">
                        </circle>
                        <polyline points="40,65 55,80 85,45" fill="none" stroke="#34D399" stroke-width="10"
                            stroke-linecap="round" stroke-linejoin="round" stroke-dasharray="100"
                            stroke-dashoffset="100" class="success-check"></polyline>
                    </svg>
                </div>

                <h3 class="text-2xl font-semibold text-green-700 mb-2">
                    <span x-text="adjustmentAmount > 0 ? 'Pembayaran Berhasil!' : 'Pengembalian Berhasil!'"></span>
                </h3>

                <!-- Info Kembalian/Pengembalian -->
                <template x-if="adjustmentKembalian > 0">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3 text-green-700">
                        <p class="text-sm font-medium">Kembalian:</p>
                        <p class="text-xl font-bold transition-all duration-300"
                            x-text="'Rp ' + formatRupiah(adjustmentKembalian)"></p>
                    </div>
                </template>

                <!-- Tombol Aksi -->
                <div class="mt-6 flex flex-col gap-3">
                    <button @click="printNota('kecil')" type="button"
                        class="px-4 py-2.5 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Cetak Nota
                    </button>

                    <button @click="closeAdjustmentSuccessModal()"
                        class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i> Kembali ke Daftar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @php
        $itemsJson = \App\Models\Item::with(['gudangItems.gudang', 'gudangItems.satuan', 'kategori'])
            ->get()
            ->map(function ($i) {
                return [
                    'id' => $i->id,
                    'kode_item' => $i->kode_item,
                    'nama_item' => $i->nama_item,
                    'kategori' => $i->kategori?->nama_kategori ?? '',
                    'gudangs' => $i->gudangItems->map(function ($ig) {
                        return [
                            'gudang_id' => $ig->gudang?->id,
                            'nama_gudang' => $ig->gudang?->nama_gudang,
                            'satuan_id' => $ig->satuan?->id,
                            'nama_satuan' => $ig->satuan?->nama_satuan,
                            'stok' => $ig->stok,
                            'harga_retail' => $ig->satuan?->harga_retail ?? 0,
                        ];
                    }),
                ];
            })
            ->toArray();
    @endphp

    <script>
        function penjualanCepatShow() {
            return {
                form: {
                    id: {{ $penjualan->id }},
                    no_faktur: @json($penjualan->no_faktur),
                    tanggal: @json($penjualan->tanggal->format('Y-m-d')),
                    is_draft: {{ (int) $penjualan->is_draft }},
                    items: []
                },

                allItems: [],
                selectedPelangganLevel: 'retail',
                subtotal: 0,
                total: 0,
                oldTotal: {{ $penjualan->total }}, // ✅ TAMBAHKAN: Total sebelum perubahan
                savedPenjualanId: null,
                showPrintModal: false,
                showAdjustmentModal: false, // ✅ TAMBAHKAN
                adjustmentAmount: 0, // ✅ TAMBAHKAN: Selisih total
                nominalAdjustment: 0, // ✅ TAMBAHKAN
                nominalAdjustmentDisplay: '',
                // Di dalam function penjualanCepatShow(), tambahkan property:
                showAdjustmentSuccessModal: false, // ✅ TAMBAHKAN
                adjustmentKembalian: 0, // ✅ TAMBAHKAN // ✅ TAMBAHKAN
                adjustmentMethod: 'cash',
                adjustmentBankName: '', // ✅ TAMBAHKAN
                isDirty: false,
                isSaving: false,
                initialForm: null,
                initialized: false,

                bankList: [{
                        name: 'BRI',
                        logo: '{{ asset('storage/images/bri.png') }}'
                    },
                    {
                        name: 'BNI',
                        logo: '{{ asset('storage/images/bni.png') }}'
                    },
                    {
                        name: 'Mandiri',
                        logo: '{{ asset('storage/images/mandiri.png') }}'
                    },
                ],

                init() {
                    this.allItems = @json($itemsJson);

                    console.log('🔍 Total Items Loaded:', this.allItems.length);

                    // ✅ KOSONGKAN ARRAY DULU
                    this.form.items = [];

                    @foreach ($penjualan->items as $it)
                        @php
                            $kategori = optional($it->item->kategori)->nama_kategori ?? '';
                            $isSpandek = str_contains(strtolower($kategori), 'spandek') || str_contains(strtolower($kategori), 'spandex');

                            // Split keterangan jika ada format gabungan
                            $keterangan = $it->keterangan ?? '';
                            $catatan_produksi = '';

                            if ($isSpandek && strpos($keterangan, ' - ') !== false) {
                                $parts = explode(' - ', $keterangan, 2);
                                $keterangan = $parts[0];
                                $catatan_produksi = $parts[1] ?? '';
                            }
                        @endphp

                        this.form.items.push({
                            item_id: {{ $it->item_id }},
                            query: {{ Js::from($it->item->nama_item ?? '') }},
                            kategori: {{ Js::from($kategori) }},
                            is_spandek: {{ Js::from($isSpandek) }},
                            showNote: false,
                            keterangan: {{ Js::from($keterangan) }},
                            catatan_produksi: {{ Js::from($catatan_produksi) }},
                            gudang_id: {{ $it->gudang_id }},
                            satuan_id: {{ $it->satuan_id }},
                            qty: {{ $it->jumlah }},
                            harga: {{ $it->harga }},
                            stok: 0,
                            manual: true,
                            _dropdownOpen: false,
                            gudangs: {!! json_encode(
                                $it->item->gudangItems->map(
                                        fn($ig) => [
                                            'gudang_id' => $ig->gudang_id,
                                            'nama_gudang' => $ig->gudang->nama_gudang ?? '',
                                            'satuan_id' => $ig->satuan_id,
                                            'nama_satuan' => $ig->satuan->nama_satuan ?? '',
                                            'stok' => $ig->stok ?? 0,
                                            'harga_retail' => $ig->satuan->harga_retail ?? 0,
                                        ],
                                    )->toArray(),
                            ) !!},
                            filteredSatuans: [],
                            results: []
                        });
                    @endforeach

                    console.log('✅ Total items loaded:', this.form.items.length); // Tambahkan log ini

                    this.$nextTick(() => {
                        this.form.items.forEach((item, idx) => {
                            this.updateSatuanOptions(idx);
                        });
                        this.recalc();
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.initialized = true;
                        this.watchFormChanges();
                    });

                    // ... kode lainnya
                },

                // ✅ TAMBAHKAN: Handle input adjustment
                handleAdjustmentInput(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (!value) {
                        this.nominalAdjustmentDisplay = '';
                        this.nominalAdjustment = 0;
                        return;
                    }

                    this.nominalAdjustment = parseInt(value);
                    this.nominalAdjustmentDisplay = new Intl.NumberFormat('id-ID').format(this.nominalAdjustment);
                },

                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
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

                recalc() {
                    this.subtotal = this.form.items.reduce(
                        (sum, it) => sum + ((+it.qty || 0) * (+it.harga || 0)),
                        0
                    );
                    this.total = this.subtotal;
                },

                // === FORMAT JUMLAH ===
                formatJumlah(val) {
                    if (val == null || val === '') return '';
                    const s = val.toString();
                    const parts = s.split('.');
                    const intPart = (parts[0] || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const decPart = parts[1] || '';
                    return decPart ? `${intPart},${decPart}` : intPart;
                },

                updateQtyFormatted(idx, val) {
                    val = (val || '').toString();
                    if (val.startsWith(',')) val = '0' + val;
                    val = val.replace(/[^0-9,]/g, '');

                    let parts = val.split(',');
                    if (parts.length > 2) {
                        parts = [parts[0], parts.slice(1).join('')];
                    }

                    parts[0] = parts[0].replace(/^0+(?=\d)/, '');
                    if (parts[1]) {
                        parts[1] = parts[1].replace(/[^0-9]/g, '');
                    }

                    const numericStr = (parts[0] ? parts[0].replace(/\./g, '') : '0') + (parts[1] ? '.' + parts[1] : '');
                    const numeric = parseFloat(numericStr) || 0;
                    this.form.items[idx].qty = numeric;

                    this.recalc();
                },

                // === TOGGLE KETERANGAN ===
                toggleItemNote(idx) {
                    const item = this.form.items[idx];
                    if (!item) return;

                    item.showNote = !item.showNote;

                    if (item.showNote && item.is_spandek && (!item.keterangan || !item.catatan_produksi)) {
                        this.notify('Untuk item spandek, isi KEDUA field: keterangan dan jenis spandek', 'info');
                    }

                    this.$nextTick(() => {
                        this.form.items = [...this.form.items];
                    });
                },

                // === TAMBAH ITEM MANUAL ===
                addItem() {
                    this.form.items.push({
                        item_id: null,
                        query: '',
                        kategori: '',
                        is_spandek: false,
                        showNote: false,
                        keterangan: '',
                        catatan_produksi: '',
                        results: [],
                        gudang_id: '',
                        gudangs: [],
                        satuan_id: '',
                        filteredSatuans: [],
                        qty: 1,
                        harga: 0,
                        stok: 0,
                        manual: false,
                        _dropdownOpen: false
                    });
                },

                removeItem(i) {
                    this.form.items.splice(i, 1);
                    this.recalc();
                },

                // === SEARCH ITEM ===
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

                // === PILIH ITEM ===
                selectItem(idx, item) {
                    const row = this.form.items[idx];
                    row.item_id = item.id;
                    row.query = item.nama_item;
                    row.results = [];
                    row.gudangs = item.gudangs || [];
                    row.manual = false;

                    if (row.showNote === undefined) {
                        row.showNote = false;
                    }

                    row.kategori = item.kategori || '';
                    row.is_spandek = row.kategori &&
                        (row.kategori.toLowerCase().includes('spandek') ||
                            row.kategori.toLowerCase().includes('spandex'));

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

                // === FILTER GUDANG ===
                getDistinctGudangs(item) {
                    if (!item.gudangs || item.gudangs.length === 0) return [];
                    const seen = new Set();
                    return item.gudangs.filter(g => {
                        if (seen.has(g.gudang_id)) return false;
                        seen.add(g.gudang_id);
                        return true;
                    });
                },

                // === UPDATE SATUAN ===
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

                updateHarga(idx) {
                    this.updateStockAndPrice(idx);
                    this.recalc();
                },

                // === HARGA BY LEVEL ===
                getHargaByLevel(g) {
                    if (!g) return 0;
                    const level = (this.selectedPelangganLevel || 'retail').toLowerCase();

                    if (level === 'grosir') return parseFloat(g.harga_grosir || g.harga_retail || 0);
                    if (level === 'partai_kecil') return parseFloat(g.partai_kecil || g.harga_retail || 0);

                    return parseFloat(g.harga_retail || 0);
                },

                // === UPDATE STOK & HARGA ===
                updateStockAndPrice(idx) {
                    const item = this.form.items[idx];
                    const selected = item.gudangs.find(
                        g => g.gudang_id == item.gudang_id && g.satuan_id == item.satuan_id
                    );

                    if (selected) {
                        item.stok = selected.stok || 0;
                        if (!item.manual) {
                            item.harga = this.getHargaByLevel(selected);
                        }
                    } else {
                        item.stok = 0;
                        if (!item.manual) {
                            item.harga = 0;
                        }
                    }

                    this.recalc();
                },

                // === FORMAT UTIL ===
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

                fmtDate(dateStr) {
                    if (!dateStr) return '-';

                    try {
                        const date = new Date(dateStr);
                        if (isNaN(date.getTime())) return '-';

                        const options = {
                            weekday: 'long',
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric'
                        };

                        return date.toLocaleDateString('id-ID', options);
                    } catch (e) {
                        console.error('Error formatting date:', e);
                        return '-';
                    }
                },

                // === BARCODE ===
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
                            this.form.items[existingIdx].qty += 1;
                            this.recalc();
                            e.target.value = '';
                            setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                            return;
                        }

                        const kategori = data.kategori || '';
                        const isSpandek = kategori &&
                            (kategori.toLowerCase().includes('spandek') ||
                                kategori.toLowerCase().includes('spandex'));

                        this.form.items.push({
                            item_id: data.id,
                            query: data.nama_item,
                            kategori: kategori,
                            is_spandek: isSpandek,
                            showNote: false,
                            keterangan: '',
                            catatan_produksi: '',
                            gudang_id: data.gudangs?.[0]?.gudang_id || '',
                            gudangs: data.gudangs || [],
                            satuan_id: '',
                            filteredSatuans: [],
                            qty: 1,
                            harga: 0,
                            stok: 0,
                            results: [],
                            manual: false,
                            _dropdownOpen: false
                        });

                        const idx = this.form.items.length - 1;
                        this.updateSatuanOptions(idx);
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

                // === VALIDASI ===
                validateBeforeSave() {
                    if (this.form.items.length === 0) {
                        this.notify('Belum ada item yang ditambahkan', 'error');
                        return false;
                    }

                    for (const item of this.form.items) {
                        if (item.is_spandek === true) {
                            if (!item.keterangan || item.keterangan.trim() === '') {
                                this.notify(`Keterangan wajib diisi untuk item: ${item.query}`, 'error');
                                return false;
                            }
                            if (!item.catatan_produksi || item.catatan_produksi.trim() === '') {
                                this.notify(`Jenis spandek wajib dipilih untuk item: ${item.query}`, 'error');
                                return false;
                            }
                        }
                    }

                    for (const item of this.form.items) {
                        if (item.qty > item.stok) {
                            this.notify(`Stok tidak cukup untuk item: ${item.query}`, 'error');
                            return false;
                        }
                    }

                    const allValid = this.form.items.every(i =>
                        i.item_id && i.gudang_id && i.satuan_id && i.qty > 0 && i.harga >= 0
                    );

                    if (!allValid) {
                        this.notify('Mohon lengkapi semua data item penjualan.', 'error');
                        return false;
                    }

                    return true;
                },

                // === UPDATE ===
                // ✅ UPDATE: Method update() - cek apakah perlu adjustment
                async update() {
                    if (!this.validateBeforeSave()) return;
                    if (this.isSaving) return;

                    this.isSaving = true;

                    const isDraftNow = this.form.is_draft == 1 ? false : this.form.is_draft;

                    const payload = {
                        no_faktur: this.form.no_faktur,
                        tanggal: this.form.tanggal,
                        is_draft: isDraftNow ? 1 : 0,
                        total: this.total,
                        items: this.form.items.map(it => {
                            let keteranganFinal = it.keterangan || '';
                            if (it.is_spandek && it.catatan_produksi) {
                                if (keteranganFinal) keteranganFinal += ' - ';
                                keteranganFinal += it.catatan_produksi;
                            }
                            return {
                                item_id: it.item_id,
                                gudang_id: it.gudang_id,
                                satuan_id: it.satuan_id,
                                jumlah: it.qty,
                                harga: it.harga,
                                total: it.qty * it.harga,
                                keterangan: keteranganFinal
                            };
                        })
                    };

                    try {
                        const res = await fetch(`/penjualan-cepat/${this.form.id}/update`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();
                        if (!res.ok) throw new Error(result.message || 'Gagal update');

                        this.notify('Perubahan disimpan.');
                        this.savedPenjualanId = this.form.id;

                        // ✅ CEK: Apakah dari draft ke final?
                        if (this.form.is_draft == 1) {
                            setTimeout(() => {
                                this.showPrintModal = true;
                            }, 100);
                            this.notify('Transaksi berhasil disimpan dan status diubah menjadi final.');
                            this.form.is_draft = 0;
                            this.initialForm = JSON.parse(JSON.stringify(this.form));
                            this.isDirty = false;
                        }
                        // ✅ CEK: Apakah ada perubahan total untuk transaksi final?
                        else if (this.form.is_draft == 0 && this.total !== this.oldTotal) {

                            // ✅ AMBIL dari response backend
                            const paymentInfo = result.payment_info || {
                                total_dibayar: 0,
                                total_penjualan_lama: this.oldTotal,
                                total_penjualan_baru: this.total
                            };

                            console.log('💰 Payment Info dari Backend:', paymentInfo);

                            // ✅ HITUNG: Sisa lama + Tambahan baru
                            const sisaLama = paymentInfo.total_penjualan_lama - paymentInfo.total_dibayar;
                            const tambahanBaru = paymentInfo.total_penjualan_baru - paymentInfo.total_penjualan_lama;
                            const nominalYangHarusDibayar = Math.max(0, sisaLama + tambahanBaru);

                            console.log('📊 Calculation:', {
                                total_lama: paymentInfo.total_penjualan_lama,
                                total_baru: paymentInfo.total_penjualan_baru,
                                sudah_dibayar: paymentInfo.total_dibayar,
                                sisa_lama: sisaLama,
                                tambahan_baru: tambahanBaru,
                                harus_bayar: nominalYangHarusDibayar
                            });

                            this.adjustmentAmount = nominalYangHarusDibayar;

                            // Reset form adjustment
                            this.nominalAdjustment = this.adjustmentAmount > 0 ? this.adjustmentAmount : 0;
                            this.nominalAdjustmentDisplay = this.adjustmentAmount > 0 ?
                                new Intl.NumberFormat('id-ID').format(this.adjustmentAmount) : '';
                            this.adjustmentMethod = 'cash';
                            this.adjustmentBankName = '';

                            setTimeout(() => {
                                this.showAdjustmentModal = true;
                            }, 100);
                        } else {
                            this.initialForm = JSON.parse(JSON.stringify(this.form));
                            this.isDirty = false;
                            this.notify('Perubahan berhasil disimpan (tidak ada perubahan total).');
                        }

                    } catch (err) {
                        console.error(err);
                        this.notify('Terjadi kesalahan saat menyimpan', 'error');
                    } finally {
                        this.isSaving = false;
                    }
                },

                // ✅ UPDATE method saveAdjustment():
                async saveAdjustment() {
                    if (this.nominalAdjustment <= 0) {
                        this.notify('Nominal pembayaran harus lebih dari 0', 'error');
                        return;
                    }

                    try {
                        // ✅ UPDATE: Keterangan dengan nama bank jika transfer
                        let keterangan = '';
                        if (this.adjustmentAmount > 0) {
                            keterangan =
                                `Pembayaran tambahan karena perubahan total transaksi (kekurangan Rp ${this.formatRupiah(this.adjustmentAmount)})`;
                            if (this.adjustmentMethod === 'transfer' && this.adjustmentBankName) {
                                keterangan += ` via Transfer ${this.adjustmentBankName}`;
                            }
                        } else {
                            keterangan =
                                `Pengembalian dana karena pengurangan total transaksi (kelebihan bayar Rp ${this.formatRupiah(Math.abs(this.adjustmentAmount))})`;
                        }

                        const payload = {
                            penjualan_id: this.form.id,
                            jumlah_bayar: this.adjustmentAmount > 0 ? this.nominalAdjustment : 0,
                            sisa: 0,
                            method: this.adjustmentAmount > 0 ? this.adjustmentMethod : 'cash',
                            keterangan: keterangan,
                            is_adjustment: true,
                            adjustment_amount: this.adjustmentAmount
                        };

                        console.log('📤 Payload ke backend:', payload); // ✅ Debug

                        const res = await fetch('/pembayaran', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const result = await res.json();
                        console.log('📥 Response dari backend:', result); // ✅ Debug

                        if (!result.success) throw new Error('Gagal menyimpan pembayaran.');

                        // Hitung kembalian
                        if (this.adjustmentAmount > 0) {
                            this.adjustmentKembalian = Math.max(0, this.nominalAdjustment - this.adjustmentAmount);
                        } else {
                            this.adjustmentKembalian = Math.abs(this.adjustmentAmount);
                        }

                        this.showAdjustmentModal = false;

                        setTimeout(() => {
                            this.showAdjustmentSuccessModal = true;
                            this.savedPenjualanId = this.form.id;
                        }, 100);

                        this.oldTotal = this.total;
                        this.initialForm = JSON.parse(JSON.stringify(this.form));
                        this.isDirty = false;

                    } catch (err) {
                        console.error('❌ Error saveAdjustment:', err);
                        this.notify('Gagal menyimpan pembayaran: ' + err.message, 'error');
                    }
                },


                async cancelDraft() {
                    if (!confirm('Yakin ingin menghapus transaksi draft ini?')) return;
                    try {
                        const res = await fetch(`/penjualan-cepat/${this.form.id}/cancel`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        if (!res.ok) throw new Error('Gagal menghapus draft.');
                        this.notify('Transaksi draft berhasil dihapus.', 'success');
                        window.location.href = '/penjualan-cepat';
                    } catch (err) {
                        console.error(err);
                        this.notify('Terjadi kesalahan saat menghapus draft.', 'error');
                    }
                },

                async printNota(type) {
                    try {
                        const res = await fetch(`/penjualan-cepat/${this.savedPenjualanId}/print?type=${type}`);
                        if (!res.ok) throw new Error("Gagal memuat nota");

                        const html = await res.text();
                        const printWindow = window.open('', '_blank', 'width=800,height=600');

                        if (!printWindow) {
                            this.notify("Popup diblokir, izinkan popup untuk melanjutkan.", "error");
                            return;
                        }

                        printWindow.document.write(html);
                        printWindow.document.close();

                        printWindow.onload = () => {
                            setTimeout(() => {
                                printWindow.focus();
                                printWindow.print();

                                printWindow.onafterprint = () => {
                                    printWindow.close();
                                    window.location.href = '/penjualan-cepat';
                                };

                                setTimeout(() => {
                                    if (!printWindow.closed) {
                                        printWindow.close();
                                    }
                                    window.location.href = '/penjualan-cepat';
                                }, 2000);

                            }, 500);
                        };

                    } catch (err) {
                        console.error(err);
                        this.notify("Gagal mencetak nota, coba lagi.", "error");
                    }
                },

                // Di dalam function penjualanCepatShow():
                closeAdjustmentSuccessModal() {
                    this.showAdjustmentSuccessModal = false;
                    setTimeout(() => {
                        window.location.href = '/penjualan-cepat';
                    }, 300);
                },

                goBack() {
                    window.location.href = '/penjualan-cepat';
                },

                notify(message, type = 'info') {
                    console.log(`[${type.toUpperCase()}] ${message}`);

                    const bg =
                        type === 'success' ? 'bg-green-500' :
                        type === 'error' ? 'bg-red-500' :
                        'bg-blue-500';

                    const el = document.createElement('div');
                    el.className = `${bg} text-white px-4 py-2 rounded-lg fixed top-5 right-5 shadow-lg z-50 transition`;
                    el.textContent = message;
                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 2500);
                },
            };
        }
    </script>
</body>

</html>
