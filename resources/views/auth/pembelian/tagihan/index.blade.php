@extends('layouts.app')

@section('title', 'Tagihan Pembelian')

@section('content')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Toasts --}}
    <div x-data class="fixed top-6 right-6 space-y-3 z-50 w-80">
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
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
                class="flex items-start gap-3 rounded-md border px-4 py-3 shadow text-sm"
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

    <div x-data="tagihanPage()" x-init="init()" class="space-y-6">

        {{-- ACTION BAR --}}
        <div
            class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h2 class="text-lg font-semibold text-slate-700">Daftar Tagihan Pembelian</h2>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Cari tagihan..." x-model="q"
                        class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                               focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-[#2e3e6a] hover:text-white"
                    :class="{ 'bg-[#344579] text-white': hasActiveFilters() }">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                    <span x-show="hasActiveFilters()" class="ml-1 bg-white text-[#344579] px-1.5 py-0.5 rounded text-xs">
                        <span x-text="activeFiltersCount()"></span>
                    </span>
                </button>
            </div>
        </div>

        {{-- ENHANCED FILTER --}}
        <div x-show="showFilter" x-collapse x-transition class="bg-white border border-slate-200 rounded-xl px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" placeholder="Cari faktur..." x-model="filters.no_faktur"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Supplier</label>
                    <input type="text" placeholder="Cari supplier..." x-model="filters.supplier"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
                        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal</label>
                    <input type="date" x-model="filters.tanggal"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700
        focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-200">
                <div class="text-sm text-slate-600">
                    <span x-text="filteredTotal()"></span> dari <span x-text="data.length"></span> tagihan
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="resetFilters()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-rotate-left mr-2"></i> Reset Filter
                    </button>
                    <button type="button" @click="showFilter=false"
                        class="px-4 py-2 rounded-lg bg-[#344579] text-white hover:bg-[#2e3e6a]">
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_tagihan')">
                                No Tagihan
                                <i class="fa-solid"
                                    :class="sortBy === 'no_tagihan' ? (sortDir === 'asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                            </th>
                            <th class="px-4 py-3 cursor-pointer" @click="toggleSort('no_faktur')">
                                No Faktur
                                <i class="fa-solid"
                                    :class="sortBy === 'no_faktur' ? (sortDir === 'asc' ? 'fa-arrow-up ml-2' :
                                        'fa-arrow-down ml-2') : 'fa-sort ml-2'"></i>
                            </th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Total</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3" x-text="r.no_tagihan"></td>
                                <td class="px-4 py-3" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3" x-text="r.tanggal"></td>
                                <td class="px-4 py-3" x-text="r.supplier"></td>
                                <td class="px-4 py-3" x-text="r.total"></td>
                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" @click="toggleActions(r.id)"
                                        class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <div x-cloak x-show="openActionId===r.id" @click.away="openActionId=null" x-transition
                                        class="absolute right-2 mt-2 w-40 bg-white shadow rounded-md border border-slate-200 z-20">
                                        <ul class="py-1">
                                            <li>
                                                <a :href="r.url"
                                                    class="block px-4 py-2 hover:bg-slate-50 text-left"
                                                    @click="openActionId=null">
                                                    <i class="fa-solid fa-eye mr-2"></i> Detail
                                                </a>
                                            </li>
                                            <li x-show="r.can_edit">
                                                <a :href="r.url.replace('show', 'edit')"
                                                    class="block px-4 py-2 hover:bg-slate-50 text-left">
                                                    <i class="fa-solid fa-pen mr-2"></i> Edit
                                                </a>
                                            </li>
                                            <li x-show="r.can_delete">
                                                <form :action="r.url.replace('show', '')" method="POST"
                                                    @submit.prevent="confirmDelete(r)">
                                                    @csrf @method('DELETE')
                                                    <button type="submit"
                                                        class="w-full text-left px-4 py-2 text-red-500 hover:bg-slate-50">
                                                        <i class="fa-solid fa-trash mr-2"></i> Hapus
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="7" class="px-4 py-6">Tidak ada data tagihan pembelian.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4">
                <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                    <button @click="goToPage(1)" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-left"></i>
                    </button>
                    <button @click="prev()" :disabled="currentPage === 1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <template x-for="p in pagesToShow()" :key="p">
                        <span>
                            <button x-show="p!=='...'" @click="goToPage(p)" x-text="p"
                                :class="{ 'bg-[#344579] text-white': currentPage === p }"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-[#2c3e6b] cursor-pointer"></button>
                            <span x-show="p==='...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                        </span>
                    </template>
                    <button @click="next()" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <button @click="goToPage(totalPages())" :disabled="currentPage === totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                        <i class="fa-solid fa-angles-right"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>

    <script>
function tagihanPage() {
    return {
        data: @json($tagihansJson),
        q: '',
        filters: { no_faktur: '', supplier: '', tanggal: '' },
        pageSize: 10,
        currentPage: 1,
        maxPageButtons: 7,
        showFilter: false,
        sortBy: 'no_tagihan',
        sortDir: 'asc',
        openActionId: null,

        init(){},

        hasActiveFilters(){ 
            return this.filters.no_faktur || this.filters.supplier || this.filters.tanggal;
        },
        activeFiltersCount(){
            let c=0;
            if(this.filters.no_faktur) c++;
            if(this.filters.supplier) c++;
            if(this.filters.tanggal) c++;
            return c;
        },

        // ===== reset filters =====
        resetFilters(){
            this.filters = { no_faktur: '', supplier: '', tanggal: '' };
            this.q = '';
            this.currentPage = 1;
            this.sortBy = 'no_tagihan';
            this.sortDir = 'asc';
            this.showFilter = false; // tutup panel filter
        },

        filteredList(){
            const q = this.q.trim().toLowerCase();
            let list = this.data.filter(r=>{
                if(q && !(`${r.no_tagihan} ${r.no_faktur} ${r.supplier}`.toLowerCase().includes(q))) return false;
                if(this.filters.no_faktur && !r.no_faktur.toLowerCase().includes(this.filters.no_faktur.toLowerCase())) return false;
                if(this.filters.supplier && !r.supplier.toLowerCase().includes(this.filters.supplier.toLowerCase())) return false;
                if(this.filters.tanggal && r.tanggal_raw !== this.filters.tanggal) return false;
                return true;
            });

            const dir = this.sortDir==='asc'?1:-1;
            list.sort((a,b)=>{
                const va = (a[this.sortBy]??'').toString().toLowerCase();
                const vb = (b[this.sortBy]??'').toString().toLowerCase();
                return va.localeCompare(vb)*dir;
            });

            return list;
        },
        filteredTotal(){ return this.filteredList().length; },
        totalPages(){ return Math.max(1, Math.ceil(this.filteredTotal()/this.pageSize)); },
        pagedData(){
            const start=(this.currentPage-1)*this.pageSize;
            return this.filteredList().slice(start,start+this.pageSize);
        },

        goToPage(n){ this.currentPage=Math.min(Math.max(1,n),this.totalPages()); this.openActionId=null; },
        prev(){ if(this.currentPage>1) this.currentPage--; },
        next(){ if(this.currentPage<this.totalPages()) this.currentPage++; },
        pagesToShow(){
            const total=this.totalPages(), max=this.maxPageButtons, cur=this.currentPage;
            if(total<=max) return Array.from({length:total},(_,i)=>i+1);
            const side=Math.floor((max-3)/2);
            const left=Math.max(2,cur-side), right=Math.min(total-1,cur+side);
            const pages=[1];
            if(left>2) pages.push('...');
            for(let i=left;i<=right;i++) pages.push(i);
            if(right<total-1) pages.push('...');
            pages.push(total);
            return pages;
        },

        toggleSort(field){
            if(this.sortBy===field){ this.sortDir=this.sortDir==='asc'?'desc':'asc'; }
            else { this.sortBy=field; this.sortDir='asc'; }
            this.currentPage=1;
        },

        toggleActions(id){ this.openActionId=this.openActionId===id?null:id; },
        confirmDelete(item){
            if(confirm(`Yakin hapus tagihan ${item.no_tagihan}?`)){
                document.querySelector(`form[action='${item.url.replace('show','')}']`).submit();
            }
        }
    }
}
</script>

@endsection
