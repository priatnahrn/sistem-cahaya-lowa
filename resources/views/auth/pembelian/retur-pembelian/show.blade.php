@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')

@section('content')
    {{-- Toast Container --}}
    <div x-data="{ toasts: [] }" x-init="$watch('toasts', () => { setTimeout(() => toasts.shift(), 4000) })" class="fixed top-6 right-6 space-y-3 z-50 w-80">
        <template x-for="(t, i) in toasts" :key="i">
            <div x-transition class="px-4 py-3 rounded-lg shadow-lg text-sm font-medium"
                :class="t.type === 'error' ?
                    'bg-rose-50 text-rose-700 border border-rose-200' :
                    'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>

    <div x-data="returEditPage()" x-init="init()" class="space-y-8">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('retur-pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Retur
                Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    {{ $retur->no_retur }}
                </span>
            </div>
        </div>

        <form @submit.prevent="update" class="space-y-6">
            @csrf

            {{-- Info Pembelian --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4">
                {{-- Row 1: Tanggal & Status --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Retur</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Status Retur</label>
                        <select x-model="form.status"
                            class="w-full px-3 py-2.5 rounded-lg border border-slate-200 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pending">Barang Masih Ada</option>
                            <option value="taken">Barang Diambil Sales</option>
                            <option value="refund">Pengembalian Uang Selesai</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: No Retur, No Pembelian, Supplier --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">No Retur</label>
                        <input type="text" :value="form.no_retur" readonly
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">No Pembelian</label>
                        <input type="text" :value="pembelianInfo" readonly
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                        <input type="text" :value="supplier" readonly
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                    </div>
                </div>
            </div>


            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700">Daftar Item Retur</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-slate-600">
                                <th class="px-4 py-3 text-center font-medium">Pilih</th>
                                <th class="px-4 py-3 text-left font-medium">Item</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Beli</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Retur</th>
                                <th class="px-4 py-3 text-right font-medium">Harga Beli</th>
                                <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(it, idx) in items" :key="it.id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" x-model="it.selected" @change="calcTotal"
                                            class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-2 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3" x-text="it.nama_item"></td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="formatNumber(it.jumlah_beli)">
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="text" :value="it.jumlah_retur"
                                            @input="e => {
                                                let val = e.target.value.replace(',', '.'); 
                                                let num = parseFloat(val);
                                                if (!isNaN(num)) {
                                                    it.jumlah_retur = num;
                                                } else {
                                                    it.jumlah_retur = '';
                                                }
                                                calcTotal();
                                            }"
                                                                                    @blur="e => {
                                                if (it.jumlah_retur !== '' && it.jumlah_retur != null) {
                                                    it.jumlah_retur = Number.isInteger(it.jumlah_retur) 
                                                        ? parseInt(it.jumlah_retur) 
                                                        : parseFloat(it.jumlah_retur);
                                                    e.target.value = it.jumlah_retur; // rapikan tampilan
                                                }
                                            }"
                                                                                    class="w-24 text-right border border-slate-200 rounded px-2 py-1.5 
                                                focus:outline-none focus:ring-2 focus:ring-blue-500 
                                                disabled:bg-slate-50 disabled:text-slate-400">

                                    </td>



                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="formatCurrency(it.harga_beli)">
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700"
                                        x-text="formatCurrency(it.subtotal)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Catatan + Total --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Catatan / Alasan Retur</label>
                    <textarea x-model="form.catatan" rows="3" placeholder="Masukkan alasan atau catatan retur..."
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
                <div class="flex justify-end items-center text-lg pt-3 border-t border-slate-100">
                    <span class="font-medium text-slate-600">Total Retur:</span>
                    <span class="ml-3 font-bold text-slate-800 text-xl" x-text="formatCurrency(form.total)"></span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('retur-pembelian.index') }}"
                    class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                    Kembali
                </a>
                <button type="submit" :disabled="!canSubmit()"
                    :class="canSubmit() ?
                        'bg-[#344579] hover:bg-[#2e3e6a] cursor-pointer' :
                        'bg-slate-300 cursor-not-allowed'"
                    class="px-5 py-2.5 rounded-lg text-white font-medium">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <script>
        function returEditPage() {
            return {
                supplier: {{ Js::from($retur->pembelian->supplier->nama_supplier ?? '') }},
                pembelianInfo: {{ Js::from($retur->pembelian->no_faktur ?? '') }},
                items: [],
                toasts: [],
                originalForm: {},

                form: {
                    pembelian_id: {{ Js::from($retur->pembelian_id) }},
                    no_retur: {{ Js::from($retur->no_retur) }},
                    tanggal: {{ Js::from($retur->tanggal->format('Y-m-d')) }},
                    catatan: {{ Js::from($retur->catatan) }},
                    total: {{ Js::from($retur->total) }},
                    status: {{ Js::from($retur->status) }}
                },

                init() {
                    this.loadItems();
                },

                async loadItems() {
                    try {
                        const res = await fetch(`/pembelian/${this.form.pembelian_id}/items`);
                        if (!res.ok) throw new Error("Gagal memuat data item");
                        const data = await res.json();

                        // Map items dengan data retur yang ada
                        const returItems = {{ Js::from($retur->items->pluck('jumlah', 'item_pembelian_id')) }};

                        this.items = data.items.map(it => {
                            const jumlahRetur = returItems[it.id] || 0;
                            return {
                                id: it.id,
                                nama_item: it.nama_item,
                                jumlah_beli: it.jumlah,
                                jumlah_retur: jumlahRetur,
                                harga_beli: it.harga_beli,
                                subtotal: jumlahRetur * it.harga_beli,
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
                    } catch (e) {
                        this.toasts.push({
                            type: 'error',
                            message: e.message
                        });
                    }
                },

                calcTotal() {
                    let total = 0;
                    this.items.forEach(it => {
                        it.subtotal = (it.jumlah_retur || 0) * it.harga_beli;
                        if (it.selected) total += it.subtotal;
                    });
                    this.form.total = total;
                },

                // 👇 Tambahin helper baru
                formatNumber(val) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 2
                    }).format(val || 0);
                },

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(val || 0);
                },

                isFormValid() {
                    if (!this.form.pembelian_id || !this.form.tanggal) return false;
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
                    return this.isFormValid() && this.isFormChanged();
                },

                async update() {
                    if (!this.canSubmit()) return;

                    try {
                        const payload = {
                            pembelian_id: this.form.pembelian_id,
                            tanggal: this.form.tanggal,
                            catatan: this.form.catatan,
                            total: this.form.total,
                            status: this.form.status,
                            items: this.items.filter(it => it.selected && it.jumlah_retur > 0).map(it => ({
                                item_pembelian_id: it.id,
                                jumlah: it.jumlah_retur
                            }))
                        };

                        const res = await fetch("{{ route('retur-pembelian.update', $retur->id) }}", {
                            method: 'PUT',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });




                        if (res.ok) {
                            const data = await res.json();
                            this.toasts.push({
                                type: 'success',
                                message: data.message
                            });
                            setTimeout(() => {
                                window.location.href = "{{ route('retur-pembelian.index') }}";
                            }, 1500);
                        } else {
                            const err = await res.json().catch(() => null);
                            if (err?.errors) {
                                Object.values(err.errors).flat().forEach(msg => {
                                    this.toasts.push({
                                        type: 'error',
                                        message: msg
                                    });
                                });
                            } else {
                                this.toasts.push({
                                    type: 'error',
                                    message: "Gagal menyimpan perubahan!"
                                });
                            }
                        }
                    } catch (e) {
                        this.toasts.push({
                            type: 'error',
                            message: e.message
                        });
                    }
                }
            }
        }
    </script>
@endsection
