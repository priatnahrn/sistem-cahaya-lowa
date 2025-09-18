@extends('layouts.app')

@section('title','Penjualan')

@section('content')
<div x-data="penjualanPage()" x-init="init()" class="space-y-6">
    {{-- ===== ACTION BAR ===== --}}
    <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('penjualan.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#3a8f70] shadow">
                <i class="fa-solid fa-plus"></i> Tambah Penjualan Baru
            </a>
            <button class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-file-export mr-2"></i> Export
            </button>
        </div>
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" placeholder="Search" x-model="q"
                       class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                              focus:outline-none focus:ring-2 focus:ring-[#4BAC87]/30 focus:border-[#4BAC87]">
            </div>
            {{-- Filter toggle --}}
            <button @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </div>
    </div>

    {{-- ===== FILTER FORM (toggle) ===== --}}
    <div x-show="showFilter" x-collapse x-transition
         class="bg-white border border-slate-200 rounded-xl px-6 py-4 grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm text-slate-500 mb-1">Tanggal</label>
            <input type="date" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Nama Pelanggan</label>
            <input type="text" placeholder="Cari Pelanggan"
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Item</label>
            <input type="text" placeholder="Cari Item"
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Nama Admin</label>
            <select class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
                <option>Pilih Admin</option>
                <option>Arianti Putri</option>
                <option>Ralph Edwards</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-slate-500 mb-1">Status</label>
            <select class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
                <option>Pilih Status</option>
                <option value="pending">Pending</option>
                <option value="lunas">Lunas</option>
                <option value="belum">Belum Lunas</option>
            </select>
        </div>
    </div>

    {{-- ===== TABLE ===== --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3">No. Faktur</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Nama Pelanggan</th>
                        <th class="px-4 py-3">Total Penjualan</th>
                        <th class="px-4 py-3">Nama Admin</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-2 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in data" :key="r.id">
                        <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                            <td class="px-4 py-3" x-text="r.no_faktur"></td>
                            <td class="px-4 py-3" x-text="r.tanggal_fmt"></td>
                            <td class="px-4 py-3">
                                <a href="#" class="text-[#4BAC87] hover:underline" x-text="r.pelanggan"></a>
                            </td>
                            <td class="px-4 py-3" x-text="formatRupiah(r.total)"></td>
                            <td class="px-4 py-3" x-text="r.admin"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                      :class="badgeClass(r.status)">
                                    <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                    <span x-text="statusLabel(r.status)"></span>
                                </span>
                            </td>
                            <td class="px-2 py-3 text-right">
                                <button class="px-2 py-1 rounded hover:bg-slate-100">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function penjualanPage(){
    return {
        showFilter:false,
        q:'',
        data:[
            {id:1,no_faktur:'TP-000000001',tanggal:'2025-08-12T08:36:00',pelanggan:'Ronald Richards',total:2500000000,admin:'Arianti Putri',status:'pending'},
            {id:2,no_faktur:'TP-000000002',tanggal:'2025-08-12T08:36:00',pelanggan:'Ralph Edwards',total:2500000000,admin:'Ralph Edwards',status:'belum'},
            {id:3,no_faktur:'TP-000000003',tanggal:'2025-08-12T08:36:00',pelanggan:'Floyd Miles',total:2500000000,admin:'Darrell Steward',status:'lunas'},
        ],
        init(){ this.data = this.data.map(r=>({...r,tanggal_fmt:this.fmtTanggal(r.tanggal)})) },
        formatRupiah(n){ return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n); },
        fmtTanggal(iso){ const d=new Date(iso);return `${d.getDate()}-${d.getMonth()+1}-${d.getFullYear()}, ${d.getHours()}:${d.getMinutes()}`;},
        badgeClass(st){ if(st==='pending') return 'bg-yellow-50 text-yellow-700 border border-yellow-200';
                        if(st==='lunas') return 'bg-green-50 text-green-700 border border-green-200';
                        return 'bg-rose-50 text-rose-700 border border-rose-200'; },
        dotClass(st){ if(st==='pending') return 'bg-yellow-400'; if(st==='lunas') return 'bg-green-500'; return 'bg-rose-500'; },
        statusLabel(st){ if(st==='pending') return 'Pending'; if(st==='lunas') return 'Lunas'; return 'Belum Lunas'; }
    }
}
</script>
@endsection
