@extends('layouts.app')

@section('title','Daftar Item')

@section('content')
    {{-- cegah flash sebelum Alpine siap --}}
    <style>[x-cloak]{display:none!important;}</style>

    <div x-data="itemsPage()" x-init="init()" class="space-y-6">
        {{-- ACTION BAR --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('items.create') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-white bg-[#344579] hover:bg-[#2e3e6a] shadow">
                    <i class="fa-solid fa-plus"></i> Tambah Item Baru
                </a>

                <button type="button" @click="exportData()"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-file-export mr-2"></i> Export
                </button>
            </div>

            <div class="flex items-center gap-3">
                {{-- Search --}}
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" placeholder="Search Kode / Nama / Supplier" x-model="q"
                           class="w-80 pl-10 pr-3 py-2 rounded-lg border border-slate-200 text-slate-600 placeholder-slate-400
                                  focus:outline-none focus:ring-2 focus:ring-[#344579]/20 focus:border-[#344579]">
                </div>

                {{-- Filter toggle --}}
                <button type="button" @click="showFilter=!showFilter"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-filter mr-2"></i> Filter
                </button>
            </div>
        </div>

        {{-- FILTER (toggle) --}}
        <div x-show="showFilter" x-collapse x-transition
             class="bg-white border border-slate-200 rounded-xl px-6 py-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm text-slate-500 mb-1">Kategori</label>
                <input type="text" placeholder="Cari Kategori" x-model="filters.kategori"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div>
                <label class="block text-sm text-slate-500 mb-1">Supplier</label>
                <input type="text" placeholder="Cari Supplier" x-model="filters.supplier"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm text-slate-700">
            </div>

            <div class="flex items-end">
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
                            <th class="px-4 py-3">Kode Item</th>
                            <th class="px-4 py-3">Nama Item</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3">Stock</th>
                            <th class="px-4 py-3 text-right">Satuan</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>

                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm" x-text="r.kode"></span>
                                </td>

                                <td class="px-4 py-3">
                                    <a :href="r.url ?? '/items/' + r.id"
                                       class="text-[#344579] font-semibold hover:underline"
                                       x-text="r.nama" @click="openActionId = null"></a>
                                    <div class="text-slate-500 text-xs mt-1" x-text="r.deskripsi"></div>
                                </td>

                                <td class="px-4 py-3" x-text="r.kategori"></td>

                                <td class="px-4 py-3">
                                    <div class="max-w-xs truncate" x-text="r.supplier"></div>
                                </td>

                                <td class="px-4 py-3" x-text="r.stock"></td>

                                <td class="px-4 py-3 text-right font-medium" x-text="r.satuan"></td>

                                <td class="px-2 py-3 text-right relative">
                                    <button type="button" @click="toggleActions(r.id)" class="px-2 py-1 rounded hover:bg-slate-100">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>

                                    {{-- actions dropdown --}}
                                    <div x-cloak x-show="openActionId === r.id" @click.away="openActionId = null" x-transition
                                         class="absolute right-2 mt-2 w-44 bg-white shadow rounded-md border border-slate-200 z-20">
                                        <ul class="py-1">
                                            <li>
                                                <a :href="r.url ?? '/items/'+ r.id"
                                                   class="block px-4 py-2 hover:bg-slate-50 text-left"
                                                   @click="openActionId = null">
                                                    <i class="fa-solid fa-eye mr-2"></i> Detail
                                                </a>
                                            </li>
                                            <li>
                                                <a :href="r.urlEdit ?? '/items/'+ r.id + '/edit'"
                                                   class="block px-4 py-2 hover:bg-slate-50 text-left"
                                                   @click="openActionId = null">
                                                    <i class="fa-solid fa-pen mr-2"></i> Edit
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
                            <td colspan="8" class="px-4 py-6">Tidak ada data item.</td>
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
                        Apakah Anda yakin ingin menghapus item
                        <span class="font-semibold" x-text="deleteItem.nama"></span> ( <span x-text="deleteItem.kode"></span> ) ?
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-slate-200 flex justify-end gap-2">
                    <button type="button" @click="closeDelete()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="button" @click="doDelete()" class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
function itemsPage(){
    return {
        showFilter:false,
        q:'',
        filters:{
            kategori:'',
            supplier:'',
        },

        // pagination
        pageSize: 5,
        currentPage: 1,
        maxPageButtons: 7,
        openActionId: null,

        // sample data (sesuaikan / fetch dari API di init jika perlu)
        data:[
            {id:1, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Gudang Asparagus', satuan:'PCS', stock:250, deskripsi:''},
            {id:2, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'PCS, BOX, SAK', stock:2500000, deskripsi:''},
            {id:3, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'PCS', stock:2500000, deskripsi:''},
            {id:4, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'PCS', stock:2500000, deskripsi:''},
            {id:5, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'PCS', stock:2500000, deskripsi:''},
            {id:6, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'PCS', stock:2500000, deskripsi:''},
            {id:7, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'BOX', stock:2500000, deskripsi:''},
            {id:8, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'BOX', stock:2500000, deskripsi:''},
            {id:9, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'BOX', stock:2500000, deskripsi:''},
            {id:10, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'BOX', stock:2500000, deskripsi:''},
            {id:11, kode:'08389328QD', nama:'Semen Full Tiga Roga Kuat Tahan', kategori:'Bahan Baku', supplier:'Toko untuk membeli sekrup lengkap dan murah banget', satuan:'BOX', stock:2500000, deskripsi:''},
        ],

        // delete modal state
        showDeleteModal:false,
        deleteItem:{},

        init(){
            this.showDeleteModal = false;
            this.deleteItem = {};
            this.openActionId = null;
            this.showFilter = false;

            window.addEventListener('pageshow', () => {
                this.showDeleteModal = false;
                this.deleteItem = {};
                this.openActionId = null;
                this.showFilter = false;
            });

            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.openActionId = null;
                    this.showDeleteModal = false;
                    this.showFilter = false;
                }
            });

            // jika mau fetch dari backend, uncomment contoh berikut:
            // fetch('/api/items').then(r => r.json()).then(js => this.data = js);
        },

        // SEARCH + FILTER
        filteredList(){
            const q = this.q.trim().toLowerCase();
            return this.data.filter(r=>{
                if(q){
                    const hay = `${r.kode} ${r.nama} ${r.supplier}`.toLowerCase();
                    if(!hay.includes(q)) return false;
                }
                if(this.filters.kategori && !r.kategori.toLowerCase().includes(this.filters.kategori.toLowerCase())) return false;
                if(this.filters.supplier && !r.supplier.toLowerCase().includes(this.filters.supplier.toLowerCase())) return false;
                return true;
            });
        },
        filteredTotal(){ return this.filteredList().length },

        // PAGINATION helpers
        totalPages(){ return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize)); },
        pagedData(){
            const start = (this.currentPage - 1) * this.pageSize;
            return this.filteredList().slice(start, start + this.pageSize);
        },
        goToPage(n){
            const t = this.totalPages();
            if(n < 1) n = 1;
            if(n > t) n = t;
            this.currentPage = n;
            this.openActionId = null;
            this.showDeleteModal = false;
        },
        prev(){ if(this.currentPage > 1) this.currentPage--; this.openActionId = null; },
        next(){ if(this.currentPage < this.totalPages()) this.currentPage++; this.openActionId = null; },

        // pagination with ellipsis
        pagesToShow(){
            const total = this.totalPages();
            const maxButtons = this.maxPageButtons;
            const current = this.currentPage;
            if(total <= maxButtons) return Array.from({length: total}, (_, i)=>i+1);

            const pages = [];
            const side = Math.floor((maxButtons - 3) / 2);
            const left = Math.max(2, current - side);
            const right = Math.min(total - 1, current + side);

            pages.push(1);
            if(left > 2) pages.push('...');
            for(let i = left; i <= right; i++) pages.push(i);
            if(right < total - 1) pages.push('...');
            pages.push(total);

            return pages;
        },

        // ACTIONS
        toggleActions(id){
            this.openActionId = (this.openActionId === id) ? null : id;
        },

        confirmDelete(item){
            this.openActionId = null;
            this.deleteItem = Object.assign({}, item);
            this.showDeleteModal = true;
        },
        closeDelete(){
            this.showDeleteModal = false;
            this.deleteItem = {};
        },
        doDelete(){
            const id = this.deleteItem.id;
            const idx = this.data.findIndex(d => d.id === id);
            if(idx !== -1){
                this.data.splice(idx, 1);
                if(this.currentPage > this.totalPages()){
                    this.currentPage = this.totalPages();
                }
            }
            this.closeDelete();
        },

        exportData(){
            alert('Fitur export belum diimplementasikan — panggil endpoint export pada backend.');
        },

        resetFilters(){
            this.filters = {kategori:'', supplier:''};
            this.q = '';
            this.currentPage = 1;
        },

        // helper formatting
        formatRupiah(v){
            if(v === null || v === undefined) return '-';
            return 'Rp' + v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    }
}
</script>
@endsection
