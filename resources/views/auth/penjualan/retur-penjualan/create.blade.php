@extends('layouts.app')

@section('title', 'Tambah Retur Penjualan')

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
            <a href="{{ route('retur-penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Retur
                Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Retur
                </span>
            </div>
        </div>

        <form @submit.prevent="save" class="space-y-6">
            @csrf

            {{-- Cari Penjualan --}}
            <div class="bg-white border border-slate-200 rounded-xl px-6 py-5 space-y-4 ">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Cari Penjualan</label>
                    <div class="relative" x-data="{ open: false }">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" x-model="penjualanQuery"
                            @input.debounce.300ms="searchPenjualan(); open = true" @focus="open = true"
                            @click.away="open = false" placeholder="Cari penjualan (No Faktur / pelanggan)..."
                            class="w-full pl-10 pr-8 py-2.5 rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">

                        <input type="hidden" name="penjualan_id" :value="form.penjualan_id">

                        {{-- Dropdown suggestion --}}
                        <div x-show="open && penjualanQuery.length >= 2" x-cloak x-transition
                            class="absolute z-30 w-full mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <div class="p-2">
                                <div x-show="penjualanResults.length === 0"
                                    class="px-3 py-2 text-sm text-slate-400 text-center italic">
                                    Tidak ada penjualan ditemukan
                                </div>
                                <template x-for="p in penjualanResults" :key="p.id">
                                    <div @click="selectPenjualan(p); open=false"
                                        class="px-3 py-2 text-sm hover:bg-slate-50 cursor-pointer rounded transition-colors">
                                        <div class="font-medium" x-text="p.no_faktur"></div>
                                        <div class="text-xs text-slate-500" x-text="p.pelanggan.nama_pelanggan"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="pelanggan" class="text-sm text-slate-600 pt-2 border-t border-slate-100">
                    <span class="font-medium">pelanggan:</span> <span x-text="pelanggan"></span>
                </div>
            </div>

            {{-- Daftar Item --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden ">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="text-base font-semibold text-slate-700">Daftar Item Penjualan</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-slate-600">
                                <th class="px-4 py-3 text-center font-medium">Pilih</th>
                                <th class="px-4 py-3 text-left font-medium">Item</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Jual</th>
                                <th class="px-4 py-3 text-right font-medium">Jumlah Retur</th>
                                <th class="px-4 py-3 text-right font-medium">Harga Jual</th>
                                <th class="px-4 py-3 text-right font-medium">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="items.length === 0">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 italic">
                                        Belum ada item, cari dulu penjualan.
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
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="it.jumlah_jual"></td>
                                    <td class="px-4 py-3 text-right">
                                        <input type="number" min="0" :max="it.jumlah_jual"
                                            x-model.number="it.jumlah_retur" @input="calcTotal" :disabled="!it.selected"
                                            class="w-24 text-right border border-slate-200 rounded px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:bg-slate-50 disabled:text-slate-400">
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600" x-text="formatCurrency(it.harga_jual)">
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-slate-700"
                                        x-text="formatCurrency(it.subtotal)"></td>

                                    {{-- Hidden input untuk dikirim hanya kalau selected --}}
                                    <template x-if="it.selected && it.jumlah_retur > 0">
                                        <td class="hidden">
                                            <input type="hidden" :name="'items[' + idx + '][item_penjualan_id]'"
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
                <a href="{{ route('retur-penjualan.index') }}"
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
        function returCreatePage() {
            return {
                pelanggan: '',
                items: [],
                penjualanQuery: '',
                penjualanResults: [],
                allPenjualans: @json($penjualansJson),
                toasts: [],

                form: {
                    penjualan_id: '',
                    catatan: '',
                    total: 0
                },

                init() {},

                searchPenjualan() {
                    const q = this.penjualanQuery.toLowerCase();
                    if (q.length < 2) {
                        this.penjualanResults = [];
                        return;
                    }
                    this.penjualanResults = this.allPenjualans.filter(p =>
                        p.no_faktur.toLowerCase().includes(q) ||
                        p.pelanggan.nama_pelanggan.toLowerCase().includes(q)
                    ).slice(0, 20);
                },

                hasSelectedItems() {
                    return this.form.penjualan_id &&
                        this.items.some(it => it.selected && it.jumlah_retur > 0) &&
                        this.form.total > 0;
                },

                selectPenjualan(p) {
                    this.form.penjualan_id = p.id;
                    this.penjualanQuery = p.no_faktur + " - " + p.pelanggan.nama_pelanggan;
                    this.penjualanResults = [];
                    this.loadItems();
                },

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
                            jumlah_jual: it.jumlah,
                            jumlah_retur: 0,
                            harga_jual: it.harga_jual,
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
                        it.subtotal = (it.jumlah_retur || 0) * it.harga_jual;
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
                    if (!this.form.penjualan_id) {
                        this.toasts.push({
                            type: 'error',
                            message: "Penjualan belum dipilih"
                        });
                        return;
                    }
                    try {
                        const payload = {
                            penjualan_id: this.form.penjualan_id,
                            catatan: this.form.catatan,
                            total: this.form.total,
                            items: this.items.filter(it => it.selected && it.jumlah_retur > 0).map(it => ({
                                item_penjualan_id: it.id,
                                jumlah: it.jumlah_retur
                            }))
                        };

                        const res = await fetch('/penjualan/retur-penjualan', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify(payload)
                        });

                        if (res.ok) {
                            window.location.href = "{{ route('retur-penjualan.index') }}";
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
