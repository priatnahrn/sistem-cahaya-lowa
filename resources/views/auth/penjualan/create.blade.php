@extends('layouts.app')

@section('title','Tambah Penjualan Baru')

@section('content')
<div class="space-y-6">

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
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="space-y-4">

            {{-- Pelanggan --}}
            <div>
                <label class="block text-sm text-slate-500 mb-2">Pelanggan</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Pilih Pelanggan" x-model="form.pelanggan"
                           class="w-full pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400">
                </div>
            </div>

            {{-- Nomor Faktur, Tanggal, Deskripsi --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm text-slate-500 mb-2">Nomor Faktur</label>
                    <input type="text" x-model="form.no_faktur"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700">
                </div>

                <div>
                    <label class="block text-sm text-slate-500 mb-2">Tanggal Penjualan</label>
                    <input type="date" x-model="form.tanggal"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700">
                </div>

                <div>
                    <label class="block text-sm text-slate-500 mb-2">Deskripsi</label>
                    <input type="text" x-model="form.deskripsi" placeholder="Deskripsi (Optional)"
                           class="w-full px-3 py-2 rounded-lg border border-slate-200 text-slate-700">
                </div>
            </div>

        </div>
    </div>

    {{-- MAIN FORM --}}
    <div x-data="penjualanCreatePage()" x-init="init()" class="space-y-6">

        {{-- DAFTAR ITEM --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 overflow-x-auto">
                <div class="min-w-[1300px]">

                    {{-- Header --}}
                    <div class="grid grid-cols-12 gap-3 text-slate-600 text-sm font-medium border-b border-slate-200 pb-3">
                        <div class="text-center w-6">#</div>
                        <div class="col-span-2 text-center">Kode/Nama Item</div>
                        <div class="col-span-1 text-center">Gudang</div>
                        <div class="col-span-1 text-center">Jumlah</div>
                        <div class="col-span-1 text-center">Satuan</div>
                        <div class="col-span-2 text-center">Harga</div>
                        <div class="col-span-1 text-center">Total</div>
                        <div class="col-span-1 text-center">Keterangan</div>
                    </div>

                    {{-- Rows --}}
                    <template x-for="(item, idx) in form.items" :key="idx">
                        <div class="grid grid-cols-12 gap-3 items-center py-3 border-b border-slate-200 w-full">

                            {{-- No --}}
                            <div class="w-6 text-slate-600 text-center" x-text="idx+1"></div>

                            {{-- Item --}}
                            <div class="col-span-2">
                                <div class="relative">
                                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                                    <input type="text" x-model="item.nama" placeholder="Cari item"
                                           class="block w-full pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-sm" />
                                </div>
                            </div>

                            {{-- Gudang --}}
                            <div class="col-span-1">
                                <select x-model="item.gudang"
                                        class="block w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                                    <option value="">Gudang</option>
                                    <option value="gudang-1">Gudang 1</option>
                                    <option value="gudang-2">Gudang 2</option>
                                </select>
                            </div>

                            {{-- Jumlah --}}
                            <div class="col-span-1">
                                <input type="number" min="0" x-model.number="item.jumlah" @input="recalc"
                                       class="block w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-center" />
                            </div>

                            {{-- Satuan --}}
                            <div class="col-span-1">
                                <select x-model="item.satuan"
                                        class="block w-full px-3 py-2 rounded-lg border border-slate-200 text-sm">
                                    <option value="">Pilih Satuan</option>
                                    <option>pcs</option>
                                    <option>box</option>
                                    <option>kg</option>
                                </select>
                            </div>

                            {{-- Harga (Rp prefix) --}}
                            <div class="col-span-2">
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="number" min="0" x-model.number="item.harga" @input="recalc" placeholder="0"
                                           class="block w-full pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-sm" />
                                </div>
                            </div>

                            {{-- Total --}}
                            <div class="col-span-1 text-right text-slate-700"
                                 x-text="formatRupiah(item.jumlah * item.harga)">
                            </div>

                            {{-- Keterangan + Hapus --}}
                            <div class="col-span-1">
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="item.keterangan" placeholder="Optional"
                                           class="flex-1 px-3 py-2 rounded-lg border border-slate-200 text-sm" />
                                    <button type="button" @click="removeItem(idx)"
                                            class="p-2 rounded text-rose-600 hover:bg-rose-50" title="Hapus item">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </template>

                    {{-- Tambah Item --}}
                    <div class="mt-4">
                        <div class="rounded-lg border-2 border-dashed border-slate-200 bg-[#EBECF2]">
                            <button type="button" @click="addItem"
                                    class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded text-slate-600">
                                <i class="fa-solid fa-plus"></i> Tambah Item Baru
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- RINGKASAN PEMBAYARAN --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4">
            <div class="w-full md:w-96 bg-white border border-slate-200 rounded-xl p-4">
                <div class="flex justify-between mb-3">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-medium" x-text="formatRupiah(subTotal)"></div>
                </div>
                <div class="flex justify-between items-center mb-3">
                    <div class="text-slate-600">Biaya Transportasi</div>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                        <input type="number" min="0" x-model.number="form.biaya_transport" @input="recalc"
                               class="w-36 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-sm">
                    </div>
                </div>
                <div class="border-t pt-3 mt-3 flex justify-between items-center">
                    <div class="text-slate-700 font-semibold">TOTAL PEMBAYARAN</div>
                    <div class="text-slate-800 text-lg font-bold" x-text="formatRupiah(totalPembayaran)"></div>
                </div>

                <div class="mt-4 flex gap-3 justify-end">
                    <a href="{{ route('penjualan.index') }}"
                       class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                    <button @click="save" type="button"
                            class="px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3f6a]">
                        Simpan
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Alpine Component --}}
<script>
function penjualanCreatePage(){
    return {
        subTotal: 0,
        totalPembayaran: 0,
        form: {
            pelanggan: '',
            no_faktur: '{{ date("Ymd") }}-{{ str_pad(1,8,"0",STR_PAD_LEFT) }}',
            tanggal: '{{ date("Y-m-d") }}',
            deskripsi: '',
            biaya_transport: 0,
            items: [
                { kode:'', gudang:'', nama:'', jumlah:0, satuan:'', harga:0 }
            ]
        },

        init(){
            this.recalc();
        },

        addItem(){
            this.form.items.push({ kode:'', gudang:'', nama:'', jumlah:0, satuan:'', harga:0 });
        },

        removeItem(idx){
            this.form.items.splice(idx,1);
            this.recalc();
        },

        recalc(){
            this.subTotal = this.form.items.reduce((acc,i)=> acc + (Number(i.jumlah||0) * Number(i.harga||0)),0);
            this.totalPembayaran = Number(this.subTotal) + Number(this.form.biaya_transport||0);
        },

        formatRupiah(n){
            n = Number(n||0);
            return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n);
        },

        save(){
            if(!this.form.pelanggan) return alert('Pilih pelanggan terlebih dahulu.');

            fetch("{{ route('penjualan.index') }}", {
                method: 'POST',
                headers: {
                    'Content-Type':'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    ...this.form,
                    subtotal: this.subTotal,
                    total: this.totalPembayaran
                })
            }).then(async res=>{
                if(res.ok){
                    alert('Penjualan berhasil disimpan');
                    window.location.href = "{{ route('penjualan.index') }}";
                } else {
                    const j = await res.json().catch(()=>({message:'Gagal menyimpan'}));
                    alert(j.message || 'Gagal menyimpan');
                }
            }).catch(e=>{
                console.error(e);
                alert('Terjadi kesalahan saat menyimpan.');
            });
        }
    }
}
</script>
@endsection
