@extends('layouts.app')

@section('title', 'Detail Tagihan Penjualan')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
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

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- TOAST --}}
    <div x-data class="fixed top-6 right-6 space-y-3 z-50 w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#ECFDF5; border-color:#A7F3D0; color:#065F46;">
                <i class="fa-solid fa-circle-check text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Berhasil</div>
                    <div>{{ session('success') }}</div>
                </div>
                <button class="ml-auto" @click="show=false"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if (session('error'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="fixed top-4 right-4 flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm z-50"
                style="background-color:#FFEAE6; border-color:#FCA5A5; color:#B91C1C;">
                <i class="fa-solid fa-circle-xmark text-lg mt-0.5"></i>
                <div>
                    <div class="font-semibold">Gagal</div>
                    <div>{{ session('error') }}</div>
                </div>
                <button class="ml-auto" @click="show=false"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
    </div>

    <div x-data="bayarTagihanPage()" x-init="init()" class="space-y-6">

        {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('tagihan-penjualan.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- Info Tagihan Card --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-5">
            <h3 class="text-lg font-semibold text-slate-700 mb-4">Informasi Tagihan</h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Tagihan</label>
                    <input type="text" value="{{ $tagihan->no_tagihan }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" value="{{ $tagihan->penjualan->no_faktur }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                    <input type="text" value="{{ $tagihan->penjualan->pelanggan->nama_pelanggan ?? '-' }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Total Tagihan</label>
                    <input type="text" value="Rp {{ number_format($tagihan->total, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 font-semibold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sudah Dibayar</label>
                    <input type="text" value="Rp {{ number_format($tagihan->jumlah_bayar, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-green-700 font-semibold">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Sisa Tagihan</label>
                    <input type="text" value="Rp {{ number_format($tagihan->sisa, 0, ',', '.') }}" readonly
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-red-600 font-bold">
                </div>
            </div>

            {{-- INFO REKENING PELANGGAN (jika ada) --}}
            @if (
                $tagihan->penjualan->pelanggan &&
                    ($tagihan->penjualan->pelanggan->nama_bank || $tagihan->penjualan->pelanggan->nomor_rekening))
                <div class="mt-4 pt-4 border-t border-slate-200">
                    <div class="bg-gradient-to-r from-indigo-50 to-blue-50 rounded-lg p-4 border border-indigo-200">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-500 flex items-center justify-center">
                                <i class="fa-solid fa-building-columns text-white"></i>
                            </div>
                            <h4 class="font-semibold text-slate-800">Informasi Rekening Pelanggan</h4>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            @if ($tagihan->penjualan->pelanggan->nama_bank)
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Bank</label>
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $tagihan->penjualan->pelanggan->nama_bank }}</p>
                                </div>
                            @endif
                            @if ($tagihan->penjualan->pelanggan->nomor_rekening)
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nomor Rekening</label>
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-semibold text-slate-800 font-mono">
                                            {{ $tagihan->penjualan->pelanggan->nomor_rekening }}</p>
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText('{{ $tagihan->penjualan->pelanggan->nomor_rekening }}'); alert('Nomor rekening disalin!')"
                                            class="text-indigo-600 hover:text-indigo-800 transition text-xs">
                                            <i class="fa-solid fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nomor Rekening</label>
                                    <p class="text-sm font-semibold text-slate-800">-</p>
                                </div>
                            @endif
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Atas Nama</label>
                                <p class="text-sm font-semibold text-slate-800">
                                    {{ $tagihan->penjualan->pelanggan->nama_pelanggan }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Daftar Item Penjualan --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="text-base font-semibold text-slate-700">Detail Item Penjualan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 font-medium">No.</th>
                            <th class="px-4 py-3 font-medium">Nama Item</th>
                            <th class="px-4 py-3 font-medium">Gudang</th>
                            <th class="px-4 py-3 font-medium">Satuan</th>
                            <th class="px-4 py-3 text-right font-medium">Jumlah</th>
                            <th class="px-4 py-3 text-right font-medium">Harga</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tagihan->penjualan->items as $index => $item)
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 text-slate-600">{{ $index + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-700">{{ $item->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->gudang->nama_gudang ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $item->satuan->nama_satuan ?? '-' }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">
                                    {{ number_format($item->jumlah, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">Rp
                                    {{ number_format($item->harga, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">Rp
                                    {{ number_format($item->total, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 border-t-2 border-slate-300">
                        <tr>
                            <td colspan="6" class="px-4 py-3 text-right font-semibold text-slate-700">Total:</td>
                            <td class="px-4 py-3 text-right font-bold text-slate-900">Rp
                                {{ number_format($tagihan->total, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Riwayat Pembayaran / Catatan --}}
        @if ($tagihan->catatan)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-[#344579]"></i>
                        Riwayat Pembayaran
                    </h3>
                </div>
                <div class="px-6 py-4">
                    <div class="space-y-3">
                        @foreach (explode("\n", $tagihan->catatan) as $index => $catatan)
                            @if (trim($catatan))
                                <div
                                    class="flex gap-3 pb-3 items-start {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-full bg-[#344579]/10 flex items-center justify-center">
                                        <span class="text-xs font-semibold text-[#344579]">{{ $index + 1 }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0 pt-2">
                                        <p class="text-sm text-slate-700 break-words">{{ trim($catatan) }}</p>
                                        @if (preg_match('/\d{2}\/\d{2}\/\d{4}/', $catatan, $matches))
                                            <p class="text-xs text-slate-500 mt-1">
                                                <i class="fa-solid fa-calendar-days mr-1"></i>
                                                {{ $matches[0] }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif


        {{-- Tombol Aksi --}}
        <div class="flex justify-end gap-3">
            @can('tagihan_penjualan.update')
                @if ($tagihan->sisa > 0)
                    <button @click="openBayarModal()"
                        class="px-5 py-2.5 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] transition shadow font-medium">
                        <i class="fa-solid fa-money-bill-wave mr-2"></i> Bayar Sekarang
                    </button>
                @else
                    <button disabled class="px-5 py-2.5 rounded-lg bg-green-100 text-green-700 cursor-not-allowed font-medium">
                        <i class="fa-solid fa-check-circle mr-2"></i> Lunas
                    </button>
                @endif
            @else
                {{-- Jika user tidak punya permission update, tombol dinonaktifkan atau disembunyikan --}}
                <button disabled title="Anda tidak memiliki akses untuk membayar tagihan"
                    class="px-5 py-2.5 rounded-lg bg-slate-300 text-slate-500 cursor-not-allowed font-medium">
                    <i class="fa-solid fa-lock mr-2"></i> Bayar Sekarang
                </button>
            @endcan
        </div>

        {{-- MODAL PEMBAYARAN (Update bagian ini saja) --}}
        <div x-cloak x-show="showBayarModal" x-transition.opacity
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 min-h-screen">
            <div class="absolute inset-0 bg-black/40" @click="closeBayarModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[480px] max-h-[85vh] z-50 overflow-hidden animate-fadeIn flex flex-col">
                {{-- HEADER (Fixed) --}}
                <div
                    class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-gradient-to-r from-[#f8fafc] to-[#f1f5f9] flex-shrink-0">
                    <h3 class="text-lg font-semibold text-[#344579]">Pembayaran Tagihan</h3>
                    <button @click="closeBayarModal()" class="text-slate-400 hover:text-slate-600 transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                {{-- KONTEN (Scrollable) --}}
                <div class="overflow-y-auto flex-1">
                    <div class="px-6 py-5 space-y-5">

                        {{-- INFO RINGKAS --}}
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 space-y-1">
                            <p class="text-sm text-slate-600">
                                <span class="font-medium">No Tagihan:</span>
                                <span class="text-slate-800">{{ $tagihan->no_tagihan }}</span>
                            </p>
                            <p class="text-sm text-slate-600">
                                <span class="font-medium">Pelanggan:</span>
                                <span
                                    class="text-slate-800">{{ $tagihan->penjualan->pelanggan->nama_pelanggan ?? '-' }}</span>
                            </p>
                            <p class="text-sm text-slate-600">
                                <span class="font-medium">No Faktur:</span>
                                <span class="text-slate-800">{{ $tagihan->penjualan->no_faktur }}</span>
                            </p>
                        </div>

                        {{-- DETAIL NOMINAL --}}
                        <div class="bg-white rounded-xl border border-slate-200 p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600">Total Tagihan:</span>
                                <span class="font-semibold text-slate-800"
                                    x-text="formatRupiah({{ $tagihan->total }})"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-600">Sudah Dibayar:</span>
                                <span class="font-semibold text-green-700"
                                    x-text="formatRupiah({{ $tagihan->jumlah_bayar }})"></span>
                            </div>
                            <div class="flex justify-between text-sm border-t border-slate-200 pt-2">
                                <span class="font-medium text-slate-700">Sisa Tagihan:</span>
                                <span class="font-bold text-rose-600" x-text="formatRupiah({{ $tagihan->sisa }})"></span>
                            </div>
                        </div>

                        {{-- INPUT NOMINAL PEMBAYARAN --}}
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
                            <p class="text-xs text-slate-500 mt-1">Maksimal: Rp
                                {{ number_format($tagihan->sisa, 0, ',', '.') }}</p>
                        </div>

                        {{-- METODE PEMBAYARAN --}}
                        <div class="space-y-3">
                            <label class="block text-sm font-medium text-slate-700">Metode Pembayaran</label>

                            <div class="flex gap-2">
                                {{-- TUNAI --}}
                                <button type="button" @click="metodeBayar = 'cash'"
                                    :class="metodeBayar === 'cash' ? 'bg-green-600 text-white border-green-600' :
                                        'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                    class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                    <i class="fa-solid fa-money-bill-wave mr-2"></i> Tunai
                                </button>

                                {{-- TRANSFER --}}
                                <button type="button" @click="metodeBayar = 'transfer'"
                                    :class="metodeBayar === 'transfer' ? 'bg-[#344579] text-white border-[#344579]' :
                                        'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'"
                                    class="flex-1 px-4 py-2.5 rounded-lg border font-medium transition">
                                    <i class="fa-solid fa-building-columns mr-2"></i> Transfer
                                </button>
                            </div>

                            {{-- PILIH BANK (untuk transfer) --}}
                            <div x-show="metodeBayar === 'transfer'" x-transition class="mt-3">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Pilih Bank:</label>
                                <div class="flex gap-3 justify-center">
                                    <template x-for="bank in bankList" :key="bank.name">
                                        <button type="button" @click="namaBank = bank.name"
                                            :class="namaBank === bank.name ? 'ring-2 ring-[#344579] border-[#344579]' :
                                                'hover:ring-1 hover:ring-slate-300'"
                                            class="h-14 bg-white border border-slate-300 w-full rounded-md flex items-center justify-center transition relative overflow-hidden">
                                            <img :src="bank.logo" :alt="bank.name"
                                                class="w-1/2 object-contain">
                                            <div x-show="namaBank === bank.name" x-transition
                                                class="absolute inset-0 bg-[#344579]/10 rounded-xl"></div>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-xs text-slate-500 mt-2 text-center">Pilih bank yang digunakan untuk
                                    transfer</p>
                            </div>
                        </div>

                        {{-- ❌ HAPUS BAGIAN CATATAN INI --}}
                        {{-- TIDAK ADA TEXTAREA CATATAN LAGI --}}

                    </div>
                </div>

                {{-- FOOTER (Fixed) --}}
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-3 bg-slate-50 flex-shrink-0">
                    <button @click="closeBayarModal()"
                        class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-white transition font-medium">
                        <i class="fa-solid fa-xmark mr-1.5"></i> Batal
                    </button>
                    <button @click="prosesPembayaran()"
                        class="px-5 py-2.5 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] shadow transition font-medium">
                        <i class="fa-solid fa-check mr-1.5"></i> Konfirmasi Pembayaran
                    </button>
                </div>
            </div>
        </div>



        {{-- MODAL SUKSES --}}
        <div x-cloak x-show="showSuccessModal" x-transition.opacity
            class="fixed inset-0 z-[99999] flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="closeSuccessModal()"></div>

            <div
                class="bg-white rounded-2xl shadow-xl w-11/12 md:w-[420px] z-50 overflow-hidden animate-fadeIn text-center p-6">

                {{-- ANIMASI SUKSES --}}
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

                <p class="text-slate-600 text-sm mb-4" x-text="successMessage"></p>

                <div class="mt-6 flex flex-col gap-3">
                    <button @click="window.location.reload()"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a] transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-eye"></i> Lihat Detail
                    </button>
                    <a href="{{ route('tagihan-penjualan.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-100 transition font-medium flex items-center justify-center gap-2">
                        <i class="fa-solid fa-list"></i> Daftar Tagihan
                    </a>
                </div>
            </div>
        </div>

    </div>

    {{-- UPDATE SCRIPT JUGA --}}
    <script>
        function bayarTagihanPage() {
            return {
                showBayarModal: false,
                showSuccessModal: false,
                nominalBayar: 0,
                nominalBayarDisplay: '',
                metodeBayar: 'cash',
                namaBank: '',
                // ❌ HAPUS: catatan: '',
                sisaTagihan: {{ $tagihan->sisa }},
                successMessage: '',

                bankList: [{
                        name: 'BRI',
                        logo: '{{ 'storage/app/public/images/bri.png' }}'
                    },
                    {
                        name: 'BNI',
                        logo: '{{ 'storage/app/public/images/bni.png' }}'
                    },
                    {
                        name: 'Mandiri',
                        logo: '{{ 'storage/app/public/images/mandiri.png'}}'
                    },
                ],

                init() {
                    console.log('✅ Bayar Tagihan Penjualan Page Ready');
                },

                openBayarModal() {
                    this.showBayarModal = true;
                    this.nominalBayar = 0;
                    this.nominalBayarDisplay = '';
                    this.metodeBayar = 'cash';
                    this.namaBank = '';
                    // ❌ HAPUS: this.catatan = '';
                },

                closeBayarModal() {
                    this.showBayarModal = false;
                },

                closeSuccessModal() {
                    this.showSuccessModal = false;
                    window.location.reload();
                },

                handleNominalInput(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (!value) {
                        this.nominalBayarDisplay = '';
                        this.nominalBayar = 0;
                        return;
                    }

                    this.nominalBayar = parseInt(value);
                    this.nominalBayarDisplay = new Intl.NumberFormat('id-ID').format(this.nominalBayar);
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        maximumFractionDigits: 0
                    }).format(n || 0);
                },

                async prosesPembayaran() {
                    // Validasi
                    if (this.nominalBayar <= 0) {
                        this.showToast('Masukkan nominal pembayaran!', 'error');
                        return;
                    }

                    if (this.nominalBayar > this.sisaTagihan) {
                        this.showToast('Nominal melebihi sisa tagihan!', 'error');
                        return;
                    }

                    if (this.metodeBayar === 'transfer' && !this.namaBank) {
                        this.showToast('Pilih bank untuk transfer!', 'error');
                        return;
                    }

                    // ✅ TIDAK ADA GENERATE CATATAN DI SINI
                    // Backend yang handle generate catatan otomatis

                    const payload = {
                        jumlah_bayar_tambahan: this.nominalBayar,
                        metode: this.metodeBayar,
                        bank: this.namaBank || null,
                        // ❌ HAPUS: catatan: catatanFinal
                    };

                    try {
                        const res = await fetch('{{ route('tagihan-penjualan.update', $tagihan->id) }}', {
                            method: 'PUT',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();

                        if (!res.ok) {
                            throw new Error(result.message || 'Gagal memproses pembayaran');
                        }

                        // Update sisa tagihan dari response
                        this.sisaTagihan = result.data.sisa;

                        // Set pesan sukses
                        if (result.data.is_lunas) {
                            this.successMessage = 'Tagihan telah dilunasi! ✓';
                        } else {
                            this.successMessage = `Sisa tagihan: ${this.formatRupiah(this.sisaTagihan)}`;
                        }

                        this.closeBayarModal();
                        this.showSuccessModal = true;

                    } catch (e) {
                        console.error('Error:', e);
                        this.showToast(e.message || 'Terjadi kesalahan saat memproses pembayaran', 'error');
                    }
                },

                showToast(message, type = 'success') {
                    const el = document.createElement('div');
                    el.className =
                        'fixed top-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-md border shadow animate-fadeIn';

                    if (type === 'error') {
                        el.style.backgroundColor = '#FFEAE6';
                        el.style.borderColor = '#FCA5A5';
                        el.style.color = '#B91C1C';
                        el.innerHTML = `<i class="fa-solid fa-circle-xmark"></i><span>${message}</span>`;
                    } else {
                        el.style.backgroundColor = '#ECFDF5';
                        el.style.borderColor = '#A7F3D0';
                        el.style.color = '#065F46';
                        el.innerHTML = `<i class="fa-solid fa-circle-check"></i><span>${message}</span>`;
                    }

                    document.body.appendChild(el);
                    setTimeout(() => el.remove(), 3500);
                },
            };
        }
    </script>
@endsection
