@extends('layouts.app')

@section('title', 'Tambah Penjualan Baru')

@section('content')
    <div x-data="penjualanCreatePage()" x-init="init()" class="space-y-6">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.index') }}" class="text-slate-500 hover:underline text-sm">Penjualan</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Penjualan Baru
                </span>
            </div>
        </div>

        {{-- INFO CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 shadow-sm">
            <div class="space-y-4">

                {{-- Pelanggan --}}
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Pelanggan</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" placeholder="Pilih Pelanggan" x-model="form.pelanggan"
                            class="w-full pl-12 pr-4 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                                   focus:outline-none focus:ring-2 focus:ring-[#4BAC87]/20 focus:border-[#4BAC87]">
                    </div>
                </div>

                {{-- Nomor Faktur, Tanggal, Deskripsi --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Nomor Faktur</label>
                        <input type="text" x-model="form.no_faktur"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Tanggal Penjualan</label>
                        <input type="date" x-model="form.tanggal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-200">
                    </div>

                    <div>
                        <label class="block text-sm text-slate-500 mb-2">Deskripsi</label>
                        <input type="text" x-model="form.deskripsi" placeholder="Deskripsi (Optional)"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700 focus:outline-none focus:ring-1 focus:ring-indigo-200">
                    </div>
                </div>

            </div>
        </div>

        {{-- MAIN FORM --}}
        <div class="space-y-6">

            {{-- DAFTAR ITEM --}}
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr class="text-slate-600">
                                <th class="px-4 py-3 w-12 text-center">#</th>
                                <th class="px-4 py-3">Kode / Nama Item</th>
                                <th class="px-4 py-3 w-40 text-center">Gudang</th>
                                <th class="px-4 py-3 w-28 text-center">Jumlah</th>
                                <th class="px-4 py-3 w-32 text-center">Satuan</th>
                                <th class="px-4 py-3 w-40 text-right">Harga</th>
                                <th class="px-4 py-3 w-40 text-right">Total</th>
                                <th class="px-4 py-3 w-56">Keterangan</th>
                                <th class="px-2 py-3 w-12"></th>
                            </tr>
                        </thead>

                        <tbody>
                            <template x-for="(item, idx) in form.items" :key="idx">
                                <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                    {{-- No --}}
                                    <td class="px-4 py-3 text-center" x-text="idx+1"></td>

                                    {{-- Nama Item --}}
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <i
                                                class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                            <input type="text" x-model="item.nama" placeholder="Cari item"
                                                class="w-full pl-12 pr-3 py-2 rounded-lg border border-slate-200 text-sm
                                                       focus:outline-none focus:ring-2 focus:ring-[#4BAC87]/20 focus:border-[#4BAC87]" />
                                        </div>
                                    </td>

                                    {{-- Gudang --}}
                                    <td class="px-4 py-3 text-center">
                                        <div class="relative inline-block w-full">
                                            <select x-model="item.gudang"
                                                class="appearance-none w-full border border-slate-200 rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-200">
                                                <option value="">Gudang</option>
                                                <option value="gudang-1">Gudang 1</option>
                                                <option value="gudang-2">Gudang 2</option>
                                            </select>
                                            <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </td>

                                    {{-- Jumlah --}}
                                    <td class="px-4 py-3 text-center">
                                        <input type="number" min="0" x-model.number="item.jumlah" @input="recalc"
                                            class="mx-auto w-20 text-center border border-slate-200 rounded-lg px-2 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-200" />
                                    </td>

                                    {{-- Satuan --}}
                                    <td class="px-4 py-3 text-center">
                                        <div class="relative inline-block w-full max-w-[120px] mx-auto">
                                            <select x-model="item.satuan"
                                                class="appearance-none w-full border border-slate-200 rounded-lg px-3 py-2 pr-10 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-200">
                                                <option value="">-</option>
                                                <option>pcs</option>
                                                <option>box</option>
                                                <option>kg</option>
                                            </select>
                                            <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </div>
                                    </td>

                                    {{-- Harga --}}
                                    <td class="px-4 py-3">
                                        <div class="relative">
                                            <span
                                                class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                            <input type="text" :value="item.hargaDisplay"
                                                @focus="e => e.target.value = item.harga"
                                                @input="onHargaInput($event, item, false)"
                                                @blur="onHargaInput($event, item, true)"
                                                class="pl-12 pr-3 w-full border border-slate-200 rounded-lg px-2 py-2 text-sm text-right focus:outline-none focus:ring-1 focus:ring-indigo-200" />
                                        </div>
                                    </td>

                                    {{-- Total --}}
                                    <td class="px-4 py-3 text-right text-slate-700"
                                        x-text="formatRupiah(item.jumlah * item.harga)"></td>

                                    {{-- Keterangan --}}
                                    <td class="px-4 py-3">
                                        <input type="text" x-model="item.keterangan" placeholder="Optional"
                                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-200" />
                                    </td>

                                    {{-- Hapus --}}
                                    <td class="px-2 py-3 text-center">
                                        <button type="button" @click="removeItem(idx)" title="Hapus item"
                                            class="p-2 rounded-md hover:bg-rose-50 text-rose-600">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Tambah Item --}}
                <div class="m-4">
                    <div class="rounded-lg border-2 border-dashed border-slate-200 bg-[#EBECF2]">
                        <button type="button" @click="addItem"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded text-slate-600">
                            <i class="fa-solid fa-plus"></i> Tambah Item Baru
                        </button>
                    </div>
                </div>

            </div>

            {{-- RINGKASAN PEMBAYARAN --}}
            <div class="flex flex-col md:flex-row md:justify-end gap-4">
                <div
                    class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6 shadow-md">

                    {{-- Subtotal --}}
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Sub Total</div>
                        <div class="font-semibold text-slate-700" x-text="formatRupiah(subTotal)"></div>
                    </div>

                    {{-- Biaya Transportasi --}}
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Biaya Transportasi</div>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                            <input type="text" :value="form.biayaTransportDisplay"
                                @focus="e => e.target.value = form.biaya_transport"
                                @input="onTransportInput($event, false)" @blur="onTransportInput($event, true)"
                                class="w-40 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-sm text-right focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        </div>
                    </div>

                    {{-- Divider --}}
                    <div class="border-t border-slate-200 pt-4 mt-4"></div>

                    {{-- Total --}}
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-700 font-bold">TOTAL PEMBAYARAN</div>
                        <div class="text-green-700 text-xl font-extrabold tracking-wide"
                            x-text="formatRupiah(totalPembayaran)"></div>
                    </div>

                    {{-- Buttons --}} <div class="mt-5 flex gap-3 justify-end"> {{-- Pending --}} <button
                            @click="savePending" type="button"
                            class="px-4 py-2 rounded-lg border border-yellow-500 text-yellow-600 hover:bg-yellow-50 ">
                            Pending </button> {{-- Simpan --}} <button @click="save" type="button"
                            class="px-4 py-2 rounded-lg bg-[#344579] text-white w-full hover:bg-green-50"> Simpan </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>
    </div>

    {{-- Alpine Component --}}
    <script>
        function penjualanCreatePage() {
            return {
                subTotal: 0,
                totalPembayaran: 0,
                form: {
                    pelanggan: 'Customer', // default pelanggan
                    no_faktur: '{{ date('Ymd') }}-{{ str_pad(1, 8, '0', STR_PAD_LEFT) }}',
                    tanggal: '{{ date('Y-m-d') }}', // default hari ini
                    deskripsi: '',
                    biaya_transport: 0,
                    biayaTransportDisplay: '0,00',
                    items: [{
                        kode: '',
                        gudang: '',
                        nama: '',
                        jumlah: 1,
                        satuan: '',
                        harga: 0,
                        hargaDisplay: '0,00',
                        keterangan: ''
                    }]
                },

                init() {
                    // fallback default tanggal sesuai browser
                    if (!this.form.tanggal) {
                        const today = new Date().toISOString().split('T')[0];
                        this.form.tanggal = today;
                    }
                    this.recalc();
                },

                addItem() {
                    this.form.items.push({
                        kode: '',
                        gudang: '',
                        nama: '',
                        jumlah: 1,
                        satuan: '',
                        harga: 0,
                        hargaDisplay: '0,00',
                        keterangan: ''
                    });
                },

                removeItem(idx) {
                    this.form.items.splice(idx, 1);
                    this.recalc();
                },

                onHargaInput(e, item, formatOnBlur = false) {
                    const raw = e.target.value.replace(/[^0-9]/g, '') || '0';
                    item.harga = parseInt(raw, 10) || 0;
                    if (formatOnBlur) {
                        item.hargaDisplay = this.formatNumber(item.harga);
                        e.target.value = item.hargaDisplay;
                    } else {
                        item.hargaDisplay = item.harga;
                    }
                    this.recalc();
                },

                onTransportInput(e, formatOnBlur = false) {
                    const raw = e.target.value.replace(/[^0-9]/g, '') || '0';
                    this.form.biaya_transport = parseInt(raw, 10) || 0;
                    if (formatOnBlur) {
                        this.form.biayaTransportDisplay = this.formatNumber(this.form.biaya_transport);
                        e.target.value = this.form.biayaTransportDisplay;
                    } else {
                        this.form.biayaTransportDisplay = this.form.biaya_transport;
                    }
                    this.recalc();
                },

                recalc() {
                    this.subTotal = this.form.items.reduce(
                        (acc, i) => acc + (Number(i.jumlah || 0) * Number(i.harga || 0)),
                        0
                    );
                    this.totalPembayaran = Number(this.subTotal) + Number(this.form.biaya_transport || 0);
                },

                formatNumber(n) {
                    return new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(n);
                },

                formatRupiah(n) {
                    return new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }).format(n);
                },

                save() {
                    if (!this.form.pelanggan) return alert('Pilih pelanggan terlebih dahulu.');

                    fetch("{{ route('penjualan.index') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ...this.form,
                            subtotal: this.subTotal,
                            total: this.totalPembayaran
                        })
                    }).then(async res => {
                        if (res.ok) {
                            alert('Penjualan berhasil disimpan');
                            window.location.href = "{{ route('penjualan.index') }}";
                        } else {
                            const j = await res.json().catch(() => ({
                                message: 'Gagal menyimpan'
                            }));
                            alert(j.message || 'Gagal menyimpan');
                        }
                    }).catch(e => {
                        console.error(e);
                        alert('Terjadi kesalahan saat menyimpan.');
                    });
                },

                savePending() {
                    if (!this.form.pelanggan) return alert('Pilih pelanggan terlebih dahulu.');

                    fetch("", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            ...this.form,
                            subtotal: this.subTotal,
                            total: this.totalPembayaran,
                            status: 'pending'
                        })
                    }).then(async res => {
                        if (res.ok) {
                            alert('Penjualan disimpan sebagai pending');
                            window.location.href = "{{ route('penjualan.index') }}";
                        } else {
                            const j = await res.json().catch(() => ({
                                message: 'Gagal menyimpan pending'
                            }));
                            alert(j.message || 'Gagal menyimpan pending');
                        }
                    }).catch(e => {
                        console.error(e);
                        alert('Terjadi kesalahan saat simpan pending.');
                    });
                }
            }
        }
    </script>
@endsection
