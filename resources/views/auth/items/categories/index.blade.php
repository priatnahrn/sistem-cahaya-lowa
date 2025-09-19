@extends('layouts.app')

@section('title','Kategori Item')

@section('content')
<style>[x-cloak]{display:none!important;}</style>

<div x-data="kategoriPage()" x-init="init()" class="space-y-6">

    {{-- ACTION BAR --}}
    <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('items.categories.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                <i class="fa-solid fa-plus"></i> Tambah Kategori Baru
            </a>

            <button type="button" @click="exportData()"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-file-export mr-2"></i> Export
            </button>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" placeholder="Search" x-model="q"
                       class="w-64 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                              focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
            </div>

            <button type="button" @click="showFilter=!showFilter"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </div>
    </div>

    {{-- FILTER --}}
    <div x-show="showFilter" x-collapse x-transition
         class="bg-white border border-slate-200 rounded-xl px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm text-slate-500 mb-1">Nama Kategori</label>
            <input type="text" placeholder="Cari Nama Kategori" x-model="filters.nama"
                   class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
        </div>

        <div class="flex items-end md:col-span-2">
            <button type="button" @click="resetFilters()"
                    class="ml-auto px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                Reset
            </button>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr class="text-left text-slate-600">
                        <th class="px-4 py-3 w-[60px]">No.</th>
                        <th class="px-4 py-3">Nama Kategori</th>
                        <th class="px-2 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(r, idx) in pagedData()" :key="r.id">
                        <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                            <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                            <td class="px-4 py-3">
                                <a :href="r.url"
                                   class="text-[#344579] font-semibold hover:underline"
                                   x-text="r.nama" @click="openActionId = null"></a>
                            </td>
                            <td class="px-2 py-3 text-right relative">
                                <button type="button" @click="toggleActions(r.id)" class="px-2 py-1 rounded hover:bg-slate-100">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div x-cloak x-show="openActionId === r.id" @click.away="openActionId = null" x-transition
                                     class="absolute right-2 mt-2 w-40 bg-white shadow rounded-md border border-slate-200 z-20">
                                    <ul class="py-1">
                                        <li>
                                            <a :href="r.url" class="block px-4 py-2 hover:bg-slate-50 text-left"
                                               @click="openActionId = null">
                                                <i class="fa-solid fa-eye mr-2"></i> Detail
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" @click="confirmDelete(r)"
                                                    class="w-full text-left px-4 py-2 text-red-500 hover:bg-slate-50">
                                                <i class="fa-solid fa-trash mr-2"></i> Hapus
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filteredTotal() === 0" class="text-center text-slate-500">
                        <td colspan="3" class="px-4 py-6">Tidak ada data kategori.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="px-6 py-4">
            <nav class="flex items-center justify-center gap-2" aria-label="Pagination">
                <button type="button" @click="goToPage(1)" :disabled="currentPage===1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                    <i class="fa-solid fa-angles-left"></i>
                </button>

                <button type="button" @click="prev()" :disabled="currentPage===1"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <template x-for="p in pagesToShow()" :key="p">
                    <span>
                        <button type="button" x-show="p !== '...'" @click="goToPage(p)" x-text="p"
                                :class="{'bg-[#344579] text-white': currentPage===p}"
                                class="mx-0.5 px-3 py-1 rounded border border-slate-200 hover:bg-slate-50"></button>
                        <span x-show="p === '...'" class="mx-1 px-3 py-1 text-slate-500">...</span>
                    </span>
                </template>

                <button type="button" @click="next()" :disabled="currentPage===totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>

                <button type="button" @click="goToPage(totalPages())" :disabled="currentPage===totalPages()"
                        class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 disabled:opacity-50">
                    <i class="fa-solid fa-angles-right"></i>
                </button>
            </nav>
        </div>
    </div>

    {{-- DELETE CONFIRM MODAL --}}
    <div x-cloak x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/40" @click="closeDelete()"></div>
        <div x-transition class="bg-white rounded-xl shadow-lg w-11/12 md:w-1/3 z-10 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-red-600">Konfirmasi Hapus</h3>
            </div>
            <div class="px-6 py-4">
                <p class="text-slate-600">
                    Apakah Anda yakin ingin menghapus kategori
                    <span class="font-semibold" x-text="deleteItem.nama"></span>?
                </p>
            </div>
            <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                <button type="button" @click="closeDelete()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Batal
                </button>
                <button type="button" @click="doDelete()"
                        class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function kategoriPage(){
    return {
        showFilter:false,
        q:'',
        filters:{ nama:'' },
        pageSize:10,
        currentPage:1,
        maxPageButtons:7,
        openActionId:null,
        showDeleteModal:false,
        deleteItem:{},

        // gunakan data dari server-side
        data: @json($categories->map(fn($k)=>[
            'id'=>$k->id,
            'nama'=>$k->nama_kategori,
            'url'=>'/kategori/'.$k->id
        ])),

        init(){
            this.showDeleteModal=false;
            this.deleteItem={};
            this.openActionId=null;
            this.showFilter=false;
        },

        filteredList(){
            const q=this.q.trim().toLowerCase();
            return this.data.filter(r=>{
                if(q && !r.nama.toLowerCase().includes(q)) return false;
                if(this.filters.nama && !r.nama.toLowerCase().includes(this.filters.nama.toLowerCase())) return false;
                return true;
            });
        },
        filteredTotal(){ return this.filteredList().length },
        totalPages(){ return Math.max(1, Math.ceil(this.filteredTotal()/this.pageSize)); },
        pagedData(){ 
            const start=(this.currentPage-1)*this.pageSize;
            return this.filteredList().slice(start,start+this.pageSize);
        },
        goToPage(n){
            const t=this.totalPages();
            if(n<1) n=1;
            if(n>t) n=t;
            this.currentPage=n;
            this.openActionId=null;
            this.showDeleteModal=false;
        },
        prev(){ if(this.currentPage>1) this.currentPage--; this.openActionId=null; },
        next(){ if(this.currentPage<this.totalPages()) this.currentPage++; this.openActionId=null; },
        pagesToShow(){
            const total=this.totalPages(), max=this.maxPageButtons, current=this.currentPage;
            if(total<=max) return Array.from({length:total},(_,i)=>i+1);
            const pages=[]; const side=Math.floor((max-3)/2);
            const left=Math.max(2,current-side);
            const right=Math.min(total-1,current+side);
            pages.push(1);
            if(left>2) pages.push('...');
            for(let i=left;i<=right;i++) pages.push(i);
            if(right<total-1) pages.push('...');
            pages.push(total);
            return pages;
        },

        toggleActions(id){ this.openActionId=(this.openActionId===id)?null:id; },
        confirmDelete(item){ this.openActionId=null; this.deleteItem=Object.assign({},item); this.showDeleteModal=true; },
        closeDelete(){ this.showDeleteModal=false; this.deleteItem={}; },
        doDelete(){ 
            const idx=this.data.findIndex(d=>d.id===this.deleteItem.id);
            if(idx!==-1) this.data.splice(idx,1);
            if(this.currentPage>this.totalPages()) this.currentPage=this.totalPages();
            this.closeDelete();
        },
        exportData(){ alert('Fitur export belum diimplementasikan'); },
        resetFilters(){ this.filters={nama:''}; this.q=''; this.currentPage=1; }
    }
}
</script>
@endsection
