@extends('layouts.app')

@section('title', 'Tambah Retur Penjualan')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Toast Container --}}
    <div x-data="toastManager()" @toast.window="addToast($event.detail)" class="fixed top-6 right-6 space-y-3 z-50 w-80">
        <template x-for="(toast, i) in toasts" :key="i">
            <div x-show="toast.show" x-transition class="flex items-start gap-3 rounded-md border px-4 py-3  text-sm"
                :class="toast.type === 'error' ?
                    'bg-rose-50 text-rose-700 border-rose-200' :
                    'bg-emerald-50 text-emerald-700 border-emerald-200'">
                <i class="fa-solid mt-0.5" :class="toast.type === 'error' ? 'fa-circle-xmark' : 'fa-circle-check'"></i>
                <div class="flex-1">
                    <div class="font-semibold" x-text="toast.type === 'error' ? 'Gagal' : 'Berhasil'"></div>
                    <div x-text="toast.message"></div>
                </div>
                <button @click="toast.show = false" class="ml-auto">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </template>
    </div>

    <div x-data="returCreatePage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('retur-penjualan.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            @csrf

            {{-- Cari Penjualan --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 ">
                <h3 class="text-base font-semibold text-slate-700 mb-4">Informasi Penjualan</h3>

                <div class="space-y-4">
                    <div x-data="{ open: false }">
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Cari Penjualan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" x-model="penjualanQuery"
                                @input.debounce.300ms="searchPenjualan(); open = true"
                                @focus="open = true; if (penjualanQuery.length >= 2) searchPenjualan()"
                                @click.outside="open = false"
                                placeholder="Ketik No. Faktur atau Nama Pelanggan (min. 2 karakter)..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm 
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       placeholder:text-slate-400">

                            {{-- Dropdown Results --}}
                            <div x-show="open && penjualanQuery.length >= 2" x-cloak x-transition
                                class="absolute z-30 w-full mt-2 bg-white border border-slate-200 rounded-lg  max-h-64 overflow-y-auto">

                                {{-- Loading State --}}
                                <div x-show="isSearching" class="px-4 py-3 text-sm text-slate-500 text-center">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                    Mencari...
                                </div>

                                {{-- No Results --}}
                                <div x-show="!isSearching && penjualanResults.length === 0" class="px-4 py-8 text-center">
                                    <i class="fa-solid fa-inbox text-3xl text-slate-300 mb-2"></i>
                                    <p class="text-sm text-slate-400">Tidak ada penjualan ditemukan</p>
                                    <p class="text-xs text-slate-400 mt-1">Coba kata kunci lain</p>
                                </div>

                                {{-- Results List --}}
                                <div x-show="!isSearching && penjualanResults.length > 0" class="py-2">
                                    <template x-for="p in penjualanResults" :key="p.id">
                                        <div @click="selectPenjualan(p); open = false"
                                            class="px-4 py-3 hover:bg-slate-50 cursor-pointer transition-colors border-b border-slate-100 last:border-0">
                                            <div class="flex items-center justify-between">
                                                <div class="flex-1">
                                                    <div class="font-medium text-slate-800 text-sm" x-text="p.no_faktur">
                                                    </div>
                                                    <div class="text-xs text-slate-500 mt-0.5">
                                                        <i class="fa-solid fa-user mr-1"></i>
                                                        <span
                                                            x-text="p.pelanggan?.nama_pelanggan || 'Pelanggan tidak diketahui'"></span>
                                                    </div>
                                                </div>
                                                <i class="fa-solid fa-chevron-right text-slate-400 text-xs ml-3"></i>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden ">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700">Daftar Item Penjualan</h3>
                    <p class="text-xs text-slate-500 mt-1">Pilih item yang akan diretur dan tentukan jumlahnya</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-slate-600">
                                <th class="px-4 py-3 text-center font-medium w-16">
                                    <input type="checkbox" @change="toggleAllItems($event.target.checked)"
                                        :checked="items.length > 0 && items.every(it => it.selected)"
                                        :disabled="items.length === 0"
                                        class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-2 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-3 text-left font-medium">Nama Item</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Terjual</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Retur</th>
                                <th class="px-4 py-3 text-right font-medium">Harga Jual</th>
                                <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Empty State --}}
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center">
                                        <i class="fa-solid fa-box-open text-5xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-400 font-medium">Belum ada item dipilih</p>
                                        <p class="text-xs text-slate-400 mt-1">Pilih penjualan terlebih dahulu untuk
                                            melihat item</p>
                                    </td>
                                </tr>
                            </template>

                            {{-- Item Rows --}}
                            <template x-for="(it, idx) in items" :key="it.id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors"
                                    :class="{ 'bg-blue-50/30': it.selected }">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" x-model="it.selected" @change="calcTotal()"
                                            class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-2 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800" x-text="it.nama_item"></div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        <span class="px-2 py-1 bg-slate-100 rounded text-xs font-medium"
                                            x-text="it.jumlah_jual"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" step="0.01" :max="it.jumlah_jual"
                                            x-model.number="it.jumlah_retur" @input="calcTotal()" :disabled="!it.selected"
                                            class="w-28 text-right border border-slate-300 rounded-lg px-3 py-1.5 text-sm
                                                   focus:outline-none focus:ring-2 focus:ring-blue-500 
                                                   disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600 font-medium"
                                        x-text="formatCurrency(it.harga_jual)">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="font-semibold text-slate-800"
                                            x-text="formatCurrency(it.subtotal)"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Catatan + Total --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">
                        Catatan / Alasan Retur
                    </label>
                    <textarea name="catatan" x-model="form.catatan" rows="4"
                        placeholder="Tuliskan alasan atau catatan retur di sini..."
                        class="w-full px-4 py-3 rounded-lg border border-slate-300 text-sm 
                               focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               placeholder:text-slate-400 resize-none"></textarea>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                    <div class="text-slate-600">
                        <div class="text-sm">Total Item: <span class="font-semibold"
                                x-text="items.filter(it => it.selected).length"></span></div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-600 mb-1">Total Retur</div>
                        <div class="text-2xl font-bold text-slate-800" x-text="formatCurrency(form.total)"></div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-4 pt-2">
                <a href="{{ route('retur-penjualan.index') }}"
                    class="px-6 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 
                           transition-colors font-medium inline-flex items-center gap-2">

                    Batal
                </a>

                <button type="submit" :disabled="!canSave()"
                    :class="canSave() ?
                        'bg-[#344579] hover:bg-[#2e3e6a] cursor-pointer' :
                        'bg-slate-300 cursor-not-allowed'"
                    class="px-6 py-2.5 rounded-lg text-white font-medium transition-colors
                           inline-flex items-center gap-2 disabled:cursor-not-allowed">
                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Retur'"></span>
                </button>
            </div>
        </form>
    </div>

    @php
        $penjualansJson = $penjualans->map(
            fn($p) => [
                'id' => $p->id,
                'no_faktur' => $p->no_faktur,
                'pelanggan' => $p->pelanggan
                    ? [
                        'id' => $p->pelanggan->id,
                        'nama_pelanggan' => $p->pelanggan->nama_pelanggan,
                    ]
                    : null,
            ],
        );
    @endphp

    <script>
        // Toast Manager Component
        function toastManager() {
            return {
                toasts: [],
                addToast(detail) {
                    const toast = {
                        type: detail.type || 'success',
                        message: detail.message || '',
                        show: true
                    };
                    this.toasts.push(toast);
                    setTimeout(() => {
                        toast.show = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t !== toast);
                        }, 300);
                    }, 4000);
                }
            };
        }

        function returCreatePage() {
            return {
                // Data
                allPenjualans: @json($penjualansJson),
                penjualanQuery: '',
                penjualanResults: [],
                isSearching: false,

                pelanggan: '',
                selectedNoFaktur: '',
                items: [],
                isSubmitting: false,

                form: {
                    penjualan_id: '',
                    catatan: '',
                    total: 0
                },

                init() {
                    console.log('=== Retur Create Page Initialized ===');
                    console.log('Total penjualans available:', this.allPenjualans.length);
                    console.log('Sample data:', this.allPenjualans.slice(0, 3));

                    // Debug: cek apakah ada pelanggan null
                    const withoutPelanggan = this.allPenjualans.filter(p => !p.pelanggan);
                    if (withoutPelanggan.length > 0) {
                        console.warn('Penjualan tanpa pelanggan:', withoutPelanggan.length);
                    }
                },

                // Search Penjualan
                searchPenjualan() {
                    const query = this.penjualanQuery.trim().toLowerCase();

                    if (query.length < 2) {
                        this.penjualanResults = [];
                        return;
                    }

                    this.isSearching = true;

                    // Simulasi delay untuk UX
                    setTimeout(() => {
                        this.penjualanResults = this.allPenjualans.filter(p => {
                            const noFaktur = (p.no_faktur || '').toLowerCase();
                            const namaPelanggan = (p.pelanggan?.nama_pelanggan || '').toLowerCase();

                            return noFaktur.includes(query) || namaPelanggan.includes(query);
                        }).slice(0, 20); // Limit 20 results

                        this.isSearching = false;

                        console.log('Search results:', this.penjualanResults.length);
                    }, 300);
                },

                // Select Penjualan
                selectPenjualan(p) {
                    this.form.penjualan_id = p.id;
                    this.selectedNoFaktur = p.no_faktur;
                    this.pelanggan = p.pelanggan?.nama_pelanggan || 'Pelanggan tidak diketahui';
                    this.penjualanQuery = `${p.no_faktur} - ${this.pelanggan}`;
                    this.penjualanResults = [];

                    this.loadItems();
                },

                // Clear Selection
                clearSelection() {
                    this.form.penjualan_id = '';
                    this.selectedNoFaktur = '';
                    this.pelanggan = '';
                    this.penjualanQuery = '';
                    this.items = [];
                    this.form.total = 0;
                },

                // Load Items dari API
                async loadItems() {
                    if (!this.form.penjualan_id) return;

                    try {
                        const res = await fetch(
                            `/penjualan/retur-penjualan/items/by-penjualan/${this.form.penjualan_id}`);

                        if (!res.ok) throw new Error("Gagal memuat data item");

                        const data = await res.json();

                        this.pelanggan = data.pelanggan;
                        this.items = data.items.map(it => ({
                            id: it.id,
                            nama_item: it.nama_item,
                            jumlah_jual: parseFloat(it.jumlah) || 0,
                            jumlah_retur: 0,
                            harga_jual: parseFloat(it.harga_jual) || 0,
                            subtotal: 0,
                            selected: false
                        }));

                        this.calcTotal();

                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'success',
                                message: `Berhasil memuat ${this.items.length} item`
                            }
                        }));
                    } catch (e) {
                        console.error('Error loading items:', e);
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: e.message || 'Gagal memuat data item'
                            }
                        }));
                    }
                },

                // Toggle All Items
                toggleAllItems(checked) {
                    this.items.forEach(it => {
                        it.selected = checked;
                        if (!checked) {
                            it.jumlah_retur = 0;
                        }
                    });
                    this.calcTotal();
                },

                // Calculate Total
                calcTotal() {
                    let total = 0;
                    this.items.forEach(it => {
                        const jumlahRetur = parseFloat(it.jumlah_retur) || 0;
                        const hargaJual = parseFloat(it.harga_jual) || 0;

                        it.subtotal = jumlahRetur * hargaJual;

                        if (it.selected) {
                            total += it.subtotal;
                        }
                    });
                    this.form.total = total;
                },

                // Validation
                canSave() {
                    return this.form.penjualan_id &&
                        this.items.some(it => it.selected && it.jumlah_retur > 0) &&
                        this.form.total > 0 &&
                        !this.isSubmitting;
                },

                // Format Currency
                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(val || 0);
                },

                // Save Retur
                async save() {
                    if (!this.canSave()) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: 'Data belum lengkap atau tidak valid'
                            }
                        }));
                        return;
                    }

                    this.isSubmitting = true;

                    try {
                        const payload = {
                            penjualan_id: this.form.penjualan_id,
                            catatan: this.form.catatan,
                            total: this.form.total,
                            items: this.items
                                .filter(it => it.selected && it.jumlah_retur > 0)
                                .map(it => ({
                                    item_penjualan_id: it.id,
                                    jumlah: it.jumlah_retur
                                }))
                        };

                        console.log('Saving payload:', payload);

                        const res = await fetch('/penjualan/retur-penjualan', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await res.json();

                        if (res.ok) {
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    type: 'success',
                                    message: 'Retur penjualan berhasil disimpan'
                                }
                            }));

                            setTimeout(() => {
                                window.location.href = "{{ route('retur-penjualan.index') }}";
                            }, 1000);
                        } else {
                            // Handle validation errors
                            if (result.errors) {
                                Object.values(result.errors).flat().forEach(msg => {
                                    window.dispatchEvent(new CustomEvent('toast', {
                                        detail: {
                                            type: 'error',
                                            message: msg
                                        }
                                    }));
                                });
                            } else {
                                window.dispatchEvent(new CustomEvent('toast', {
                                    detail: {
                                        type: 'error',
                                        message: result.message || 'Gagal menyimpan retur'
                                    }
                                }));
                            }
                            this.isSubmitting = false;
                        }
                    } catch (e) {
                        console.error('Save error:', e);
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: 'Terjadi kesalahan saat menyimpan: ' + e.message
                            }
                        }));
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
@endsection
