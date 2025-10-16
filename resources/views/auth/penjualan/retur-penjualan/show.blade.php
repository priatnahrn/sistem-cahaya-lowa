@extends('layouts.app')

@section('title', 'Detail Retur Penjualan')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Toast Container --}}
    <div x-data="toastManager()" @toast.window="addToast($event.detail)" class="fixed top-6 right-6 space-y-3 z-50 w-80">
        <template x-for="(toast, i) in toasts" :key="i">
            <div x-show="toast.show" x-transition class="flex items-start gap-3 rounded-md border px-4 py-3 text-sm"
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

    <div x-data="returEditPage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('retur-penjualan.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        <form @submit.prevent="update" class="space-y-6">
            @csrf

            {{-- Info Retur --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5">
                <h3 class="text-base font-semibold text-slate-700 mb-4">Informasi Retur</h3>

                <div class="space-y-4">
                    {{-- Row 1: No Retur & Tanggal --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">No Retur</label>
                            <input type="text" :value="form.no_retur" readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-700 font-medium">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Tanggal Retur <span class="text-red-500">*</span>
                            </label>
                            <input type="date" x-model="form.tanggal"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-700 
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    {{-- Row 2: No Penjualan & Pelanggan --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">No Penjualan</label>
                            <input type="text" :value="penjualanInfo" readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-700">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                            <input type="text" :value="pelanggan" readonly
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 bg-slate-50 text-slate-700">
                        </div>
                    </div>

                    {{-- Row 3: Status --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Status Retur <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select x-model="form.status"
                                class="w-full px-4 py-2.5 pr-10 rounded-lg border border-slate-300 text-slate-700 
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       appearance-none bg-white cursor-pointer">
                                <option value="pending">⏳ Pending - Barang Masih Ada di Customer</option>
                                <option value="taken">📦 Taken - Barang Sudah Dikembalikan Customer</option>
                                <option value="refund">💰 Refund - Pengembalian Uang Selesai</option>
                            </select>
                            <i class="fa-solid fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            <template x-if="form.status === 'pending'">
                                <span>💡 Status <strong>Pending</strong>: Retur tercatat, stok belum berubah</span>
                            </template>
                            <template x-if="form.status === 'taken'">
                                <span>⚠️ Status <strong>Taken</strong>: Barang dikembalikan customer, <strong>stok akan ditambah</strong></span>
                            </template>
                            <template x-if="form.status === 'refund'">
                                <span>✅ Status <strong>Refund</strong>: Pengembalian uang selesai, transaksi lengkap</span>
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700">Daftar Item Retur</h3>
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
                                <th class="px-4 py-3 text-center font-medium">Gudang</th>
                                <th class="px-4 py-3 text-center font-medium">Satuan</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Jual</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Retur</th>
                                <th class="px-4 py-3 text-right font-medium">Harga Jual</th>
                                <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center">
                                        <i class="fa-solid fa-box-open text-5xl text-slate-300 mb-3"></i>
                                        <p class="text-slate-400 font-medium">Tidak ada item</p>
                                    </td>
                                </tr>
                            </template>

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
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 bg-slate-100 rounded text-xs" x-text="it.gudang"></span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 bg-slate-100 rounded text-xs" x-text="it.satuan"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        <span class="px-2 py-1 bg-slate-100 rounded text-xs font-medium"
                                            x-text="formatNumber(it.jumlah_jual)"></span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input 
                                            type="text" 
                                            :value="it.jumlah_retur"
                                            @input="handleJumlahInput($event, it)"
                                            @blur="handleJumlahBlur($event, it)"
                                            :disabled="!it.selected"
                                            placeholder="0"
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
                    <textarea x-model="form.catatan" rows="4"
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

                <button type="submit" :disabled="!canSubmit()"
                    :class="canSubmit() ?
                        'bg-[#344579] hover:bg-[#2e3e6a] cursor-pointer' :
                        'bg-slate-300 cursor-not-allowed'"
                    class="px-6 py-2.5 rounded-lg text-white font-medium transition-colors
                           inline-flex items-center gap-2 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-save"></i>
                    <span x-text="isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>

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

        function returEditPage() {
            return {
                pelanggan: {{ Js::from($retur->penjualan->pelanggan->nama_pelanggan ?? 'Tidak ada pelanggan') }},
                penjualanInfo: {{ Js::from($retur->penjualan->no_faktur ?? '') }},
                items: [],
                originalForm: {},
                isSubmitting: false,

                form: {
                    penjualan_id: {{ Js::from($retur->penjualan_id) }},
                    no_retur: {{ Js::from($retur->no_retur) }},
                    tanggal: {{ Js::from($retur->tanggal->format('Y-m-d')) }},
                    catatan: {{ Js::from($retur->catatan) }},
                    total: {{ Js::from($retur->total) }},
                    status: {{ Js::from($retur->status) }}
                },

                init() {
                    console.log('=== Retur Edit Page Initialized ===');
                    this.loadItems();
                },

                async loadItems() {
                    try {
                        const res = await fetch(`/penjualan/retur-penjualan/items/by-penjualan/${this.form.penjualan_id}`);
                        if (!res.ok) throw new Error("Gagal memuat data item");
                        
                        const data = await res.json();
                        const returItems = {{ Js::from($retur->items->pluck('jumlah', 'item_penjualan_id')) }};

                        this.items = data.items.map(it => {
                            const jumlahRetur = parseFloat(returItems[it.id]) || 0;
                            return {
                                id: it.id,
                                nama_item: it.nama_item,
                                gudang: it.gudang,
                                satuan: it.satuan,
                                jumlah_jual: parseFloat(it.jumlah) || 0,
                                jumlah_retur: jumlahRetur,
                                harga_jual: parseFloat(it.harga_jual) || 0,
                                subtotal: jumlahRetur * (parseFloat(it.harga_jual) || 0),
                                selected: jumlahRetur > 0
                            };
                        });

                        this.calcTotal();

                        // Simpan snapshot awal
                        this.originalForm = JSON.parse(JSON.stringify({
                            ...this.form,
                            items: this.items.map(it => ({
                                id: it.id,
                                selected: it.selected,
                                jumlah_retur: it.jumlah_retur
                            }))
                        }));

                        console.log('Items loaded:', this.items.length);
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

                handleJumlahInput(e, item) {
                    let val = e.target.value.replace(',', '.');
                    
                    if (val === '' || val === '0') {
                        item.jumlah_retur = 0;
                    } else {
                        let num = parseFloat(val);
                        if (!isNaN(num) && num >= 0) {
                            item.jumlah_retur = num;
                        }
                    }
                    
                    this.calcTotal();
                },

                handleJumlahBlur(e, item) {
                    if (item.jumlah_retur === '' || item.jumlah_retur == null) {
                        item.jumlah_retur = 0;
                    }
                    
                    const formatted = Number.isInteger(item.jumlah_retur) 
                        ? parseInt(item.jumlah_retur).toString()
                        : parseFloat(item.jumlah_retur).toString();
                    
                    e.target.value = formatted;
                    this.calcTotal();
                },

                toggleAllItems(checked) {
                    this.items.forEach(it => {
                        it.selected = checked;
                        if (!checked) {
                            it.jumlah_retur = 0;
                        }
                    });
                    this.calcTotal();
                },

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

                formatNumber(val) {
                    const num = parseFloat(val) || 0;
                    return Number.isInteger(num) 
                        ? num.toString()
                        : num.toFixed(2).replace(/\.?0+$/, '');
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    }).format(val || 0);
                },

                isFormValid() {
                    if (!this.form.penjualan_id || !this.form.tanggal || !this.form.status) return false;
                    return this.items.some(it => it.selected && it.jumlah_retur > 0);
                },

                isFormChanged() {
                    const currentItems = this.items.map(it => ({
                        id: it.id,
                        selected: it.selected,
                        jumlah_retur: it.jumlah_retur
                    }));

                    const current = {
                        ...this.form,
                        items: currentItems
                    };

                    return JSON.stringify(current) !== JSON.stringify(this.originalForm);
                },

                canSubmit() {
                    return this.isFormValid() && this.isFormChanged() && !this.isSubmitting;
                },

                async update() {
                    if (!this.canSubmit()) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: 'Data belum lengkap atau tidak ada perubahan'
                            }
                        }));
                        return;
                    }

                    this.isSubmitting = true;

                    try {
                        const payload = {
                            penjualan_id: this.form.penjualan_id,
                            tanggal: this.form.tanggal,
                            catatan: this.form.catatan,
                            total: this.form.total,
                            status: this.form.status,
                            items: this.items
                                .filter(it => it.selected && it.jumlah_retur > 0)
                                .map(it => ({
                                    item_penjualan_id: it.id,
                                    jumlah: it.jumlah_retur
                                }))
                        };

                        console.log('Update payload:', payload);

                        const res = await fetch("{{ route('retur-penjualan.update', $retur->id) }}", {
                            method: 'PUT',
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
                                    message: result.message || 'Retur berhasil diperbarui'
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
                                        message: result.message || 'Gagal memperbarui retur'
                                    }
                                }));
                            }
                            this.isSubmitting = false;
                        }
                    } catch (e) {
                        console.error('Update error:', e);
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