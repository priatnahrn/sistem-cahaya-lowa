{{-- resources/views/penjualan/index.blade.php --}}
@extends('layouts.app')

@section('title','Penjualan')

@section('content')
<div x-data="penjualanPage()" x-init="init()" class="space-y-6">
    {{-- Header + Actions --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-slate-700">Penjualan</h1>
        <div class="flex items-center gap-3">
            <button class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-file-export mr-2"></i> Export
            </button>
            <a href="#"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#4BAC87] hover:bg-[#3a8f70] shadow">
                <i class="fa-solid fa-plus"></i> Tambah Penjualan Baru
            </a>
        </div>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            {{-- More Actions --}}
            <div x-data="{open:false}" class="relative">
                <button @click="open=!open"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-ellipsis mr-2"></i> More Actions
                </button>
                <div x-show="open" @click.outside="open=false" x-transition
                     class="absolute z-10 mt-2 w-52 bg-white border border-slate-200 rounded-xl shadow">
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Hapus Terpilih</button>
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Mark Lunas</button>
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50">Cetak</button>
                </div>
            </div>

            {{-- Sort --}}
            <div x-data="{open:false}" class="relative">
                <button @click="open=!open"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Sort <i class="fa-solid fa-sort ml-2"></i>
                </button>
                <div x-show="open" @click.outside="open=false" x-transition
                     class="absolute z-10 mt-2 w-56 bg-white border border-slate-200 rounded-xl shadow">
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50" @click="sortBy('tanggal')">Tanggal</button>
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50" @click="sortBy('total')">Total</button>
                    <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50" @click="sortBy('status')">Status</button>
                </div>
            </div>

            {{-- Filter --}}
            <div x-data="{open:false}" class="relative">
                <button @click="open=!open"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Filter <i class="fa-solid fa-filter ml-2"></i>
                </button>
                <div x-show="open" @click.outside="open=false" x-transition
                     class="absolute z-10 mt-2 w-64 bg-white border border-slate-200 rounded-xl shadow p-3 space-y-3">
                    <div class="text-sm text-slate-600">Status</div>
                    <div class="flex flex-wrap gap-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-slate-300" value="pending" x-model="filter.status"> Pending
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-slate-300" value="lunas" x-model="filter.status"> Lunas
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="rounded border-slate-300" value="belum" x-model="filter.status"> Belum Lunas
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button class="text-sm px-3 py-1.5 rounded border border-slate-200" @click="resetFilter()">Reset</button>
                        <button class="text-sm px-3 py-1.5 rounded text-white bg-[#4BAC87]" @click="applyFilter(); open=false">Terapkan</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search --}}
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
            <input type="text" placeholder="Search" x-model="q"
                   class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                          focus:outline-none focus:ring-2 focus:ring-[#4BAC87]/30 focus:border-[#4BAC87]">
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3 border-b">
                            <input type="checkbox" class="rounded border-slate-300" @change="toggleAll($event)">
                        </th>
                        <th class="px-4 py-3 border-b">No. Faktur</th>
                        <th class="px-4 py-3 border-b">Tanggal</th>
                        <th class="px-4 py-3 border-b">Nama Pelanggan</th>
                        <th class="px-4 py-3 border-b">Total Penjualan</th>
                        <th class="px-4 py-3 border-b">Nama Admin</th>
                        <th class="px-4 py-3 border-b">Status</th>
                        <th class="px-2 py-3 border-b"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(r, idx) in paged" :key="r.id">
                        <tr class="hover:bg-slate-50 text-slate-700">
                            <td class="px-4 py-3 border-b">
                                <input type="checkbox" class="rounded border-slate-300" :value="r.id" x-model="selected">
                            </td>
                            <td class="px-4 py-3 border-b" x-text="r.no_faktur"></td>
                            <td class="px-4 py-3 border-b" x-text="r.tanggal_fmt"></td>
                            <td class="px-4 py-3 border-b">
                                <a href="#" class="text-[#4BAC87] hover:underline" x-text="r.pelanggan"></a>
                            </td>
                            <td class="px-4 py-3 border-b" x-text="formatRupiah(r.total)"></td>
                            <td class="px-4 py-3 border-b" x-text="r.admin"></td>
                            <td class="px-4 py-3 border-b">
                                <span class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                      :class="badgeClass(r.status)">
                                    <span class="w-2 h-2 rounded-full" :class="dotClass(r.status)"></span>
                                    <span x-text="statusLabel(r.status)"></span>
                                </span>
                            </td>
                            <td class="px-2 py-3 border-b text-right">
                                <div x-data="{open:false}" class="relative">
                                    <button @click="open=!open" class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div x-show="open" @click.outside="open=false" x-transition
                                         class="absolute right-0 mt-1 w-40 bg-white border border-slate-200 rounded-lg shadow text-sm">
                                        <a href="#" class="block px-3 py-2 hover:bg-slate-50">Lihat</a>
                                        <a href="#" class="block px-3 py-2 hover:bg-slate-50">Edit</a>
                                        <button class="w-full text-left px-3 py-2 hover:bg-slate-50 text-red-600">Hapus</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Footer: results + page size + pagination --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 px-4 py-3 bg-white">
            <div class="text-sm text-slate-500">
                Result: <span x-text="from()"></span> - <span x-text="to()"></span> of <span x-text="filtered.length"></span>
            </div>

            <div class="flex items-center gap-3">
                <div class="relative">
                    <select x-model.number="pageSize"
                            class="appearance-none pl-3 pr-8 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
                        <option>10</option><option>16</option><option>25</option><option>50</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </div>

                <nav class="flex items-center gap-1">
                    <button class="px-3 py-2 rounded border border-slate-200 text-slate-600 hover:bg-slate-50"
                            :disabled="page===1" @click="page--">&lt;</button>

                    <template x-for="p in pages()" :key="p">
                        <button class="px-3 py-2 rounded border"
                                :class="p===page ? 'bg-[#4BAC87] text-white border-[#4BAC87]' : 'border-slate-200 text-slate-700 hover:bg-slate-50'"
                                @click="page=p" x-text="p"></button>
                    </template>

                    <button class="px-3 py-2 rounded border border-slate-200 text-slate-600 hover:bg-slate-50"
                            :disabled="page===pageCount()" @click="page++">&gt;</button>
                </nav>
            </div>
        </div>
    </div>
</div>

{{-- Alpine state & dummy data --}}
<script>
function penjualanPage(){
    return {
        q: '',
        sortKey: null,
        sortDir: 'asc',
        filter: { status: [] }, // ['pending','lunas','belum']
        data: [
            // id, no_faktur, tanggal (ISO), pelanggan, total (number), admin, status: pending|lunas|belum
            {id:1, no_faktur:'TP-000000001', tanggal:'2025-08-12T08:36:00', pelanggan:'Ronald Richards',  total:2500000000, admin:'Arianti Putri',  status:'pending'},
            {id:2, no_faktur:'TP-000000002', tanggal:'2025-08-12T08:36:00', pelanggan:'Ralph Edwards',    total:2500000000, admin:'Ralph Edwards', status:'belum'},
            {id:3, no_faktur:'TP-000000003', tanggal:'2025-08-12T08:36:00', pelanggan:'Floyd Miles',      total:2500000000, admin:'Darrell Steward',status:'lunas'},
            {id:4, no_faktur:'TP-000000004', tanggal:'2025-08-12T08:36:00', pelanggan:'Devon Lane',       total:2500000000, admin:'Kathryn Murphy', status:'belum'},
            {id:5, no_faktur:'TP-000000005', tanggal:'2025-08-12T08:36:00', pelanggan:'Marvin McKinney',  total:2500000000, admin:'Arlene McCoy',   status:'lunas'},
            {id:6, no_faktur:'TP-000000006', tanggal:'2025-08-12T08:36:00', pelanggan:'Jenny Wilson',     total:2500000000, admin:'Leslie Alexander',status:'belum'},
            {id:7, no_faktur:'TP-000000007', tanggal:'2025-08-12T08:36:00', pelanggan:'Guy Hawkins',      total:2500000000, admin:'Annette Black',  status:'lunas'},
            {id:8, no_faktur:'TP-000000008', tanggal:'2025-08-12T08:36:00', pelanggan:'Savannah Nguyen',  total:2500000000, admin:'Savannah Nguyen',status:'belum'},
            {id:9, no_faktur:'TP-000000009', tanggal:'2025-08-12T08:36:00', pelanggan:'Annette Black',    total:2500000000, admin:'Ronald Richards',status:'lunas'},
            {id:10,no_faktur:'TP-000000010', tanggal:'2025-08-12T08:36:00', pelanggan:'Theresa Webb',     total:2500000000, admin:'Jane Cooper',    status:'lunas'},
            {id:11,no_faktur:'TP-000000011', tanggal:'2025-08-12T08:36:00', pelanggan:'Kristin Watson',   total:2500000000, admin:'Devon Lane',     status:'pending'},
            // tambah dummy lagi bila perlu...
        ],
        selected: [],
        page: 1,
        pageSize: 16,

        init(){
            // pre-format tanggal untuk tampilan
            this.data = this.data.map(r => ({...r, tanggal_fmt: this.fmtTanggal(r.tanggal)}));
        },

        get filtered(){
            let rows = this.data.filter(r =>
                [r.no_faktur, r.pelanggan, r.admin].join(' ').toLowerCase().includes(this.q.toLowerCase())
            );
            if(this.filter.status.length){
                rows = rows.filter(r => this.filter.status.includes(r.status));
            }
            if(this.sortKey){
                rows.sort((a,b)=>{
                    const A = a[this.sortKey], B = b[this.sortKey];
                    if (A < B) return this.sortDir==='asc' ? -1 : 1;
                    if (A > B) return this.sortDir==='asc' ? 1 : -1;
                    return 0;
                });
            }
            return rows;
        },

        get paged(){
            const start = (this.page-1)*this.pageSize;
            return this.filtered.slice(start, start+this.pageSize);
        },

        pages(){
            const count = this.pageCount();
            // simple: tampilkan 1..count (bisa dibuat elipsis kalau mau)
            return Array.from({length: count}, (_,i)=>i+1);
        },
        pageCount(){ return Math.max(1, Math.ceil(this.filtered.length / this.pageSize)); },
        from(){ return (this.page-1)*this.pageSize + 1; },
        to(){ return Math.min(this.page*this.pageSize, this.filtered.length); },

        formatRupiah(n){ return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n); },
        fmtTanggal(iso){
            const d = new Date(iso);
            const pad = n => String(n).padStart(2,'0');
            return `${pad(d.getDate())}-${pad(d.getMonth()+1)}-${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },

        sortBy(key){
            if(this.sortKey===key){ this.sortDir = this.sortDir==='asc' ? 'desc' : 'asc'; }
            else { this.sortKey = key; this.sortDir = 'asc'; }
        },
        resetFilter(){ this.filter.status=[]; },
        applyFilter(){ this.page=1; },

        toggleAll(e){
            if(e.target.checked){ this.selected = this.paged.map(r=>r.id); }
            else { this.selected = []; }
        },

        badgeClass(st){
            if(st==='pending') return 'bg-yellow-50 text-yellow-700 border border-yellow-200';
            if(st==='lunas') return 'bg-green-50 text-green-700 border border-green-200';
            return 'bg-rose-50 text-rose-700 border border-rose-200'; // belum
        },
        dotClass(st){
            if(st==='pending') return 'bg-yellow-400';
            if(st==='lunas') return 'bg-green-500';
            return 'bg-rose-500';
        },
        statusLabel(st){
            if(st==='pending') return 'Pending';
            if(st==='lunas') return 'Lunas';
            return 'Belum Lunas';
        }
    }
}
</script>
@endsection
