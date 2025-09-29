@extends('layouts.app')

@section('title', 'Tambah Retur Pembelian')

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

    <div x-data="returCreatePage()" x-init="init()" class="space-y-8">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('retur-pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Retur
                Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Retur
                </span>
            </div>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            @csrf

            {{-- Cari Pembelian --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4 ">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cari Pembelian</label>
                    <div class="relative" x-data="{ open: false }">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="pembelianQuery"
                            @input.debounce.300ms="searchPembelian(); open = true" @focus="open = true"
                            @click.away="open = false" placeholder="Cari pembelian (No Faktur / Supplier)..."
                            class="w-full pl-10 pr-8 py-2.5 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                        <input type="hidden" name="pembelian_id" :value="form.pembelian_id">

                        {{-- Dropdown suggestion --}}
                        <div x-show="open && pembelianQuery.length >= 2" x-cloak x-transition
                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2">
                                <div x-show="pembelianResults.length === 0"
                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                    Tidak ada pembelian ditemukan
                                </div>
                                <template x-for="p in pembelianResults" :key="p.id">
                                    <div @click="selectPembelian(p); open=false"
                                        class="px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer rounded transition-colors">
                                        <div class="font-medium" x-text="p.no_faktur"></div>
                                        <div class="text-xs text-slate-500" x-text="p.supplier.nama_supplier"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="supplier" class="text-sm text-slate-600 pt-2 border-t border-slate-100">
                    <span class="font-medium">Supplier:</span> <span x-text="supplier"></span>
                </div>
            </div>

            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden ">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700">Daftar Item Pembelian</h3>
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
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">
                                        Belum ada item, cari dulu pembelian.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(it, idx) in items" :key="it.id">
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="px-4 py-3 text-center">
                                        <input type="checkbox" x-model="it.selected" @change="calcTotal"
                                            class="w-5 h-5 text-indigo-600 rounded border-gray-300 focus:ring-2 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3" x-text="it.nama_item"></td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="it.jumlah_beli"></td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" :max="it.jumlah_beli"
                                            x-model.number="it.jumlah_retur" @input="calcTotal" :disabled="!it.selected"
                                            class="w-24 text-right border border-slate-200 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="formatCurrency(it.harga_beli)">
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700"
                                        x-text="formatCurrency(it.subtotal)"></td>

                                    {{-- Hidden input untuk dikirim hanya kalau selected --}}
                                    <template x-if="it.selected && it.jumlah_retur > 0">
                                        <td class="hidden">
                                            <input type="hidden" :name="'items[' + idx + '][item_pembelian_id]'"
                                                :value="it.id">
                                            <input type="hidden" :name="'items[' + idx + '][jumlah]'"
                                                :value="it.jumlah_retur">
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Catatan + Total --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-5 ">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Catatan / Alasan Retur</label>
                    <textarea name="catatan" x-model="form.catatan" rows="3" placeholder="Masukkan alasan atau catatan retur..."
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
                    Batal
                </a>
                <button type="submit" :disabled="!hasSelectedItems()"
                    :class="hasSelectedItems() ?
                        'bg-[#344579] hover:bg-[#2e3e6a] cursor-pointer' :
                        'bg-slate-300 cursor-not-allowed'"
                    class="px-5 py-2.5 rounded-lg text-white bg-[#344579] font-medium disabled:cursor-not-allowed">
                    Simpan
                </button>

            </div>
        </form>
    </div>

    @php
        // preload pembelian untuk search
        $pembeliansJson = $pembelians->map(
            fn($p) => [
                'id' => $p->id,
                'no_faktur' => $p->no_faktur,
                'supplier' => [
                    'id' => $p->supplier->id,
                    'nama_supplier' => $p->supplier->nama_supplier,
                ],
            ],
        );
    @endphp

    <script>
        function returCreatePage() {
            return {
                supplier: '',
                items: [],
                pembelianQuery: '',
                pembelianResults: [],
                allPembelians: @json($pembeliansJson),
                toasts: [],

                form: {
                    pembelian_id: '',
                    catatan: '',
                    total: 0
                },

                init() {},

                searchPembelian() {
                    const q = this.pembelianQuery.toLowerCase();
                    if (q.length < 2) {
                        this.pembelianResults = [];
                        return;
                    }
                    this.pembelianResults = this.allPembelians.filter(p =>
                        p.no_faktur.toLowerCase().includes(q) ||
                        p.supplier.nama_supplier.toLowerCase().includes(q)
                    ).slice(0, 20);
                },

                hasSelectedItems() {
                    return this.form.pembelian_id &&
                        this.items.some(it => it.selected && it.jumlah_retur > 0) &&
                        this.form.total > 0;
                },


                selectPembelian(p) {
                    this.form.pembelian_id = p.id;
                    this.pembelianQuery = p.no_faktur + " - " + p.supplier.nama_supplier;
                    this.pembelianResults = [];
                    this.loadItems();
                },

                async loadItems() {
                    if (!this.form.pembelian_id) return;
                    try {
                        const res = await fetch(`/pembelian/${this.form.pembelian_id}/items`);
                        if (!res.ok) throw new Error("Gagal memuat data item");
                        const data = await res.json();
                        this.supplier = data.supplier;
                        this.items = data.items.map(it => ({
                            id: it.id,
                            nama_item: it.nama_item,
                            jumlah_beli: it.jumlah,
                            jumlah_retur: 0,
                            harga_beli: it.harga_beli,
                            subtotal: 0,
                            selected: false
                        }));
                        this.calcTotal();
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

                formatCurrency(val) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 0
                    }).format(val || 0);
                },

                async save() {
                    if (!this.form.pembelian_id) {
                        this.toasts.push({
                            type: 'error',
                            message: "Pembelian belum dipilih"
                        });
                        return;
                    }
                    try {
                        const payload = {
                            pembelian_id: this.form.pembelian_id,
                            catatan: this.form.catatan,
                            total: this.form.total,
                            items: this.items.filter(it => it.selected && it.jumlah_retur > 0).map(it => ({
                                item_pembelian_id: it.id,
                                jumlah: it.jumlah_retur
                            }))
                        };

                        const res = await fetch('/pembelian/retur-pembelian/store', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            window.location.href = "{{ route('retur-pembelian.index') }}";
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
                                    message: "Gagal menyimpan retur!"
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
