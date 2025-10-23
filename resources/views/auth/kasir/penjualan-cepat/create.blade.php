<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Penjualan Cepat - Kasir</title>
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
    <script src="//unpkg.com/alpinejs" defer></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

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

    <div x-data="penjualanCepatFull()" x-init="init()" class="min-h-screen bg-slate-100">
        <!-- HEADER -->
        <header class="bg-[#344579] text-white py-4 px-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('penjualan-cepat.index') }}"
                    class="bg-[#2c3e6b] hover:bg-[#24355b] px-3 py-2 rounded-md flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <h1 class="font-semibold text-lg">Penjualan Cepat</h1>
            </div>

            <div class="text-right text-sm leading-tight">
                <div>No. Faktur: <span class="font-bold" x-text="form.no_faktur"></span></div>
                <div x-text="fmtDate(form.tanggal)"></div>
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
                                    <td class="px-5 py-4 text-center font-medium align-middle" x-text="idx + 1"></td>

                                    <td class="px-5 py-4 align-middle">
                                        <div class="relative">
                                            <div class="flex items-center gap-2">
                                                <button type="button" @click.prevent="toggleItemNote(idx)"
                                                    :title="item.showNote ? 'Sembunyikan keterangan' : 'Tambah keterangan'"
                                                    :class="{
                                                        'text-blue-600': item.showNote,
                                                        'text-slate-500 hover:text-blue-600': !item.showNote
                                                    }"
                                                    class="transition focus:outline-none">
                                                    <i class="fa-solid fa-note-sticky text-[15px]"></i>
                                                </button>

                                                <div class="relative flex-1">
                                                    <input type="text" x-model="item.query"
                                                        @input.debounce.300ms="searchItem(idx)"
                                                        @focus="item.query.length >= 2 && searchItem(idx); item._dropdownOpen = true"
                                                        @click="item.query.length >= 2 ? item._dropdownOpen = true : null"
                                                        @keydown.escape="item._dropdownOpen = false"
                                                        placeholder="Cari item"
                                                        class="w-full pl-4 pr-10 py-2.5 rounded-lg border border-slate-300 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition" />

                                                    <span x-show="!item.item_id" x-cloak
                                                        x-transition.opacity.duration.150ms
                                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 p-1 rounded-full pointer-events-none">
                                                        <i class="fa-solid fa-magnifying-glass"></i>
                                                    </span>

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

                                            <template x-if="item.showNote">
                                                <div class="mt-3 space-y-3"
                                                    x-transition:enter="transition ease-out duration-300"
                                                    x-transition:enter-start="opacity-0"
                                                    x-transition:enter-end="opacity-100">

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
                                                                    <option value="Spandek Warna">Spandek Warna</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </template>

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

                                                        <select x-model="item.gudang_id"
                                                            @change="updateSatuanOptions(idx)"
                                                            class="absolute inset-0 opacity-0 cursor-pointer">
                                                            <template x-for="g in getDistinctGudangs(item)"
                                                                :key="g.gudang_id">
                                                                <option :value="g.gudang_id" x-text="g.nama_gudang">
                                                                </option>
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

                                    <td class="px-5 py-4 text-center align-middle">
                                        <input type="text" :value="item.qty ? formatJumlah(item.qty) : ''"
                                            @input="updateQtyFormatted(idx, $event.target.value)"
                                            class="no-spinner w-24 text-center border border-slate-300 rounded-lg px-2 py-2.5 
                                        focus:border-blue-500 focus:ring-1 focus:ring-blue-200"
                                            inputmode="numeric" pattern="[0-9]*" />
                                    </td>

                                    <td class="px-5 py-4 align-middle">
                                        <div class="relative">
                                            <select x-model="item.satuan_id" @change="updateHarga(idx)"
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

                                    <td class="px-5 py-4 text-right align-middle">
                                        <div class="relative">
                                            <span
                                                class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                            <input type="text" :value="formatRupiah(item.harga)"
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

                                    <td
                                        class="px-5 py-4 text-right font-semibold text-slate-800 align-middle whitespace-nowrap">
                                        Rp <span x-text="formatRupiah((item.qty||0) * (item.harga||0))"></span>
                                    </td>

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

                    <div class="m-4">
                        <button type="button" @click="addItem"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded border-2 border-dashed border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 transition">
                            <i class="fa-solid fa-plus"></i> Tambah Item Baru
                        </button>
                    </div>
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

                <div class="flex gap-3 mt-6">
                    <button @click="savePending()" type="button"
                        class="w-full bg-white hover:bg-yellow-600 text-yellow-600 hover:text-white border border-yellow-600 py-2.5 rounded-md text-sm font-medium">
                        Pending
                    </button>

                    <button @click="save()" type="button"
                        class="w-full bg-[#344579] hover:bg-[#2d3f6b] text-white py-2.5 rounded-md text-sm font-medium flex items-center justify-center gap-2">
                       Simpan 
                    </button>
                </div>
            </div>
        </main>

        <!-- 💳 MODAL PEMBAYARAN -->
        <div x-cloak x-show="showPaymentModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40 " @click="showPaymentModal=false"></div>

            <div class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn">
                <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-[#344579]">Pembayaran</h3>
                    <button @click="showPaymentModal=false" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                        <p class="text-sm text-slate-600"><span class="font-medium">No Faktur:</span>
                            <span class="text-slate-800" x-text="penjualanData?.no_faktur || '-'"></span>
                        </p>
                        <p class="text-sm text-slate-600"><span class="font-medium">Total Tagihan:</span>
                            <span class="text-slate-800 font-semibold"
                                x-text="formatRupiah(penjualanData?.total || 0)"></span>
                        </p>
                    </div>

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

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                        <div class="flex gap-2">
                            <button type="button" @click="pilihMetode('cash')"
                                :class="metodePembayaran === 'cash'
                                    ?
                                    'bg-green-600 text-white border-green-600' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-money-bill-wave mr-2"></i> Tunai
                            </button>

                            <button type="button" @click="pilihMetode('transfer')"
                                :class="metodePembayaran === 'transfer'
                                    ?
                                    'bg-[#344579] text-white border-[#344579]' :
                                    'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                <i class="fa-solid fa-building-columns mr-2"></i> Transfer
                            </button>
                        </div>

                        <div x-show="metodePembayaran === 'transfer'" x-transition
                            class="flex gap-3 mt-3 justify-center">
                            <template x-for="bank in bankList" :key="bank.name">
                                <button type="button" @click="namaBank = bank.name"
                                    :class="namaBank === bank.name ? 'ring-2 ring-[#344579] border-[#344579]' :
                                        'hover:ring-1 hover:ring-slate-300'"
                                    class="h-14 bg-white border border-slate-300 w-full rounded-md flex items-center justify-center transition relative overflow-hidden ">
                                    <img :src="bank.logo" :alt="bank.name" class="w-1/2 object-contain">
                                    <div x-show="namaBank === bank.name" x-transition
                                        class="absolute inset-0 bg-[#344579]/10 rounded-xl"></div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50">
                    <button @click="showPaymentModal=false"
                        class="w-[30%] px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition">
                        Batal
                    </button>
                    <button @click="simpanPembayaran()"
                        class="w-[70%] px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] shadow transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-print"></i> Bayar & Cetak
                    </button>
                </div>
            </div>
        </div>

        <!-- ✅ MODAL PEMBAYARAN BERHASIL -->
        <div x-cloak x-show="showSuccessModal" x-transition.opacity
            class="fixed inset-0 z-[99999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50 " @click="closeSuccessModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">

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

                <h3 class="text-2xl font-semibold text-green-700 mb-2">Pembayaran Berhasil!</h3>

                <!-- 💰 KEMBALIAN -->
                <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-3 text-green-700">
                    <p class="text-sm font-medium">Kembalian:</p>
                    <p class="text-xl font-bold transition-all duration-300" x-text="'Rp ' + formatRupiah(kembalian ?? 0)">
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    <button @click="closeSuccessModal()"
                        class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 transition font-medium flex items-center justify-center gap-2">
 Kembali
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function penjualanCepatFull() {
            return {
                form: {
                    no_faktur: '',
                    tanggal: '',
                    items: []
                },

                allItems: [],
                selectedPelangganLevel: 'retail',
                subtotal: 0,
                total: 0,
                showPaymentModal: false,
                penjualanId: null,
                penjualanData: null,
                metodePembayaran: 'cash',
                namaBank: '',
                bankList: [{
                        name: 'BRI',
                        logo: '{{ url('storage/app/public/images/bri.png') }}'
                    },
                    {
                        name: 'BNI',
                        logo: '{{ url('storage/app/public/images/bni.png') }}'
                    },
                    {
                        name: 'Mandiri',
                        logo: '{{ url('storage/app/public/images/mandiri.png') }}'
                    },
                ],
                nominalBayarDisplay: '',
                nominalBayar: 0,
                kembalian: 0,
                showSuccessModal: false,
                isNavigating: false,

                init() {
                    // ✅ RESET SEMUA MODAL STATE DI AWAL
                    this.showPaymentModal = false;
                    this.showSuccessModal = false;
                    this.penjualanId = null;
                    this.penjualanData = null;
                    this.nominalBayarDisplay = '';
                    this.nominalBayar = 0;
                    this.kembalian = 0;

                    this.form.no_faktur = @json($noFaktur ?? '');
                    this.form.tanggal = @json(now()->format('Y-m-d'));
                    this.allItems = @json($itemsJson ?? []);

                    this.focusScanner();
                    document.addEventListener('click', (e) => {
                        const tag = e.target.tagName?.toLowerCase();
                        if (!['input', 'textarea', 'select'].includes(tag)) this.focusScanner();
                    });
                },

                focusScanner() {
                    setTimeout(() => this.$refs.barcodeInput?.focus(), 100);
                },

                recalc() {
                    this.subtotal = this.form.items.reduce(
                        (sum, it) => sum + ((+it.qty || 0) * (+it.harga || 0)),
                        0
                    );
                    this.total = this.subtotal;
                    this.updateKembalian();
                },

                updateKembalian() {
                    this.kembalian = Math.max(0, (this.nominalBayar || 0) - (this.total || 0));
                },

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

                toggleItemNote(idx) {
                    const item = this.form.items[idx];
                    if (!item) return;

                    const currentValue = item.showNote || false;
                    item.showNote = !currentValue;

                    this.$nextTick(() => {
                        this.form.items = [...this.form.items];
                    });

                    if (item.showNote && item.is_spandek && (!item.keterangan || !item.catatan_produksi)) {
                        this.notify('Untuk item spandek, isi KEDUA field: keterangan dan jenis spandek', 'info');
                    }
                },

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
                        harga_manual: false,
                        _dropdownOpen: false
                    });
                },

                removeItem(i) {
                    this.form.items.splice(i, 1);
                    this.recalc();
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

                updateHarga(idx) {
                    this.updateStockAndPrice(idx);
                    this.recalc();
                },

                getHargaByLevel(g) {
                    if (!g) return 0;
                    const level = (this.selectedPelangganLevel || 'retail').toLowerCase();

                    if (level === 'grosir') return parseFloat(g.harga_grosir || g.harga_retail || 0);
                    if (level === 'partai_kecil') return parseFloat(g.partai_kecil || g.harga_retail || 0);

                    return parseFloat(g.harga_retail || 0);
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
                            harga_manual: false,
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

                isItemNoteComplete(item) {
                    if (!item.is_spandek) {
                        return true;
                    }
                    return item.keterangan &&
                        item.keterangan.trim() !== '' &&
                        item.catatan_produksi &&
                        item.catatan_produksi.trim() !== '';
                },

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

                async save() {
                    if (!this.validateBeforeSave()) return;

                    try {
                        const res = await fetch('/penjualan-cepat/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                no_faktur: this.form.no_faktur,
                                tanggal: this.form.tanggal,
                                total: this.total,
                                items: this.form.items.map(it => {
                                    let keteranganFinal = it.keterangan || '';

                                    if (it.is_spandek && it.catatan_produksi) {
                                        if (keteranganFinal) {
                                            keteranganFinal += ' - ';
                                        }
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
                            }),
                        });

                        const data = await res.json();

                        if (data.success) {
                            this.showPaymentModal = true;
                            this.penjualanId = data.id;
                            this.penjualanData = {
                                no_faktur: this.form.no_faktur,
                                total: this.total
                            };
                            this.notify('Transaksi disimpan. Lanjut ke pembayaran.', 'success');
                        } else {
                            this.notify(data.message || 'Gagal menyimpan transaksi', 'error');
                        }
                    } catch (err) {
                        this.notify('Terjadi kesalahan koneksi', 'error');
                    }
                },

                async savePending() {
                    if (this.form.items.length === 0) {
                        this.notify('Belum ada item dalam transaksi.', 'error');
                        return;
                    }

                    try {
                        const res = await fetch('/penjualan-cepat/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                no_faktur: this.form.no_faktur,
                                tanggal: this.form.tanggal,
                                total: this.total,
                                status_bayar: 'unpaid',
                                is_draft: 1,
                                items: this.form.items.map(it => {
                                    let keteranganFinal = it.keterangan || '';

                                    if (it.is_spandek && it.catatan_produksi) {
                                        if (keteranganFinal) {
                                            keteranganFinal += ' - ';
                                        }
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
                            }),
                        });

                        const data = await res.json();
                        if (data.success) {
                            this.notify('Transaksi disimpan sebagai pending.', 'success');
                            this.form.items = [];
                            this.recalc();
                        } else {
                            this.notify(data.message || 'Gagal menyimpan transaksi pending', 'error');
                        }
                    } catch (err) {
                        this.notify('Terjadi kesalahan koneksi saat menyimpan pending.', 'error');
                    }
                },

                handleNominalInput(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (!value) {
                        this.nominalBayarDisplay = '';
                        this.nominalBayar = 0;
                        this.kembalian = 0;
                        return;
                    }

                    this.nominalBayar = parseInt(value);
                    this.nominalBayarDisplay = new Intl.NumberFormat('id-ID').format(this.nominalBayar);

                    if (this.penjualanData) {
                        const total = parseInt(this.penjualanData.total);
                        this.kembalian = Math.max(0, this.nominalBayar - total);
                    }
                },

                pilihMetode(metode) {
                    this.metodePembayaran = metode;
                    this.namaBank = '';
                },

                async simpanPembayaran() {
                    if (!this.penjualanId || this.nominalBayar <= 0) {
                        this.notify('Nominal pembayaran belum diisi.', 'error');
                        return;
                    }

                    const payload = {
                        penjualan_id: this.penjualanId,
                        jumlah_bayar: this.nominalBayar,
                        sisa: 0,
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

                        const result = await res.json();

                        if (!result.success) throw new Error('Pembayaran gagal disimpan.');

                        // ✅ Tutup modal pembayaran
                        this.showPaymentModal = false;

                        // ✅ Langsung print nota
                        await this.printNotaKecil();

                        // ✅ Tampilkan modal sukses dengan kembalian
                        setTimeout(() => {
                            this.showSuccessModal = true;
                        }, 500);

                    } catch (e) {
                        this.notify(e.message || 'Gagal menyimpan pembayaran.', 'error');
                    }
                },

                // ✅ PRINT NOTA KECIL (otomatis setelah bayar)
                async printNotaKecil() {
                    if (!this.penjualanId) {
                        this.notify('Data penjualan tidak ditemukan', 'error');
                        return;
                    }

                    try {
                        const url = `/penjualan/${this.penjualanId}/print?type=kecil`;
                        const res = await fetch(url);

                        if (!res.ok) {
                            throw new Error(`HTTP ${res.status}: Gagal memuat nota`);
                        }

                        const html = await res.text();

                        if (!html || html.trim().length < 100) {
                            throw new Error('Response HTML kosong atau tidak valid');
                        }

                        const printWindow = window.open('', '_blank', 'width=800,height=600');

                        if (!printWindow) {
                            this.notify("Popup diblokir, izinkan popup untuk melanjutkan.", 'error');
                            return;
                        }

                        printWindow.document.write(html);
                        printWindow.document.close();

                        printWindow.onload = () => {
                            setTimeout(() => {
                                printWindow.print();
                                
                                // ✅ Auto close setelah print
                                printWindow.onafterprint = () => {
                                    printWindow.close();
                                };
                            }, 500);
                        };

                    } catch (err) {
                        console.error('❌ Print error:', err);
                        this.notify(`Gagal mencetak nota: ${err.message}`, 'error');
                    }
                },

                closeSuccessModal() {
                    if (this.isNavigating) return;

                    this.isNavigating = true;
                    this.showSuccessModal = false;

                    // ✅ Reset form untuk transaksi baru
                    setTimeout(() => {
                        this.form.items = [];
                        this.penjualanId = null;
                        this.penjualanData = null;
                        this.nominalBayarDisplay = '';
                        this.nominalBayar = 0;
                        this.kembalian = 0;
                        this.metodePembayaran = 'cash';
                        this.namaBank = '';
                        this.recalc();
                        this.isNavigating = false;
                        
                        // ✅ Reload halaman untuk generate nomor faktur baru
                        window.location.reload();
                    }, 300);
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