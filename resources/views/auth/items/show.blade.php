@extends('layouts.app')

@section('title', 'Detail Item')

@section('content')
    @php
        $canUpdate = auth()->user()->can('items.update');
    @endphp

    <div class="space-y-6 w-full" x-data="itemsEdit()" x-init="init()">

         {{-- Breadcrumb --}}
        <div>
            <a href="{{ route('items.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- ✅ Info Alert jika tidak bisa edit --}}
        @cannot('items.update')
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 flex items-start gap-3">
                <i class="fa-solid fa-info-circle text-blue-600 mt-0.5"></i>
                <div class="text-sm text-blue-700">
                    <p class="font-medium">Mode Lihat Saja</p>
                    <p>Anda tidak memiliki izin untuk mengubah data item ini.</p>
                </div>
            </div>
        @endcannot

        {{-- TAB HEADER --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex flex-wrap gap-3 md:gap-6">
            <template x-for="(step, i) in steps" :key="i">
                <button type="button" @click="currentStep = i"
                    :class="currentStep === i ? 'text-[#334579] border-b-2 border-[#334579]' : 'text-slate-600'"
                    class="pb-2 text-sm font-medium whitespace-nowrap">
                    <span x-text="step.title"></span>
                </button>
            </template>
        </div>

        {{-- FORM --}}
        <form action="{{ route('items.update', $item->id) }}" method="POST" enctype="multipart/form-data"
            class="space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="satuan_primary_index" x-model="primarySatuanIndex">

            {{-- STEP 1: INFO ITEM --}}
            <div x-show="currentStep === 0" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-info-circle text-[#344579]"></i> Info Item
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    {{-- Barcode --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-sm text-slate-600 mb-1">Barcode</label>
                        <div class="p-4 border border-slate-200 rounded-lg bg-gray-50 flex justify-center overflow-x-auto">
                            {!! DNS1D::getBarcodeHTML($item->kode_item, 'C128', 2, 60) !!}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Kode: {{ $item->kode_item }}</p>
                    </div>

                    {{-- Kode Item --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Kode Item <span class="text-rose-600">*</span>
                        </label>
                        <input name="kode_item" x-model="form.kode_item" {{ $canUpdate ? '' : 'disabled readonly' }}
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 {{ $canUpdate ? 'focus:ring-1 focus:ring-[#344579] focus:border-[#344579]' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}" />
                    </div>

                    {{-- Nama Item --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Nama Item <span class="text-rose-600">*</span>
                        </label>
                        <input name="nama_item" x-model="form.nama_item" {{ $canUpdate ? '' : 'disabled readonly' }}
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 {{ $canUpdate ? 'focus:ring-1 focus:ring-[#344579] focus:border-[#344579]' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}" />
                    </div>

                    {{-- Stok Minimal --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Stok Minimal <span class="text-rose-600">*</span>
                        </label>
                        <input name="stok_minimal" x-model="form.stok_minimal" type="number" {{ $canUpdate ? '' : 'disabled readonly' }}
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 {{ $canUpdate ? 'focus:ring-1 focus:ring-[#344579] focus:border-[#344579]' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}" />
                    </div>

                    {{-- Kategori Item --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Kategori Item <span class="text-rose-600">*</span>
                        </label>
                        <select name="kategori_item_id" x-model="form.kategori_item_id" {{ $canUpdate ? '' : 'disabled' }}
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 {{ $canUpdate ? 'focus:ring-1 focus:ring-[#344579] focus:border-[#344579]' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategori_items ?? [] as $k)
                                <option value="{{ $k->id }}" @if (old('kategori_item_id', $item->kategori_item_id) == $k->id) selected @endif>
                                    {{ $k->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Foto Item --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Foto Item</label>
                        @if($canUpdate)
                            <div @click="$refs.fileInput.click()"
                                class="cursor-pointer border-2 border-dashed border-slate-300 rounded-lg p-6 flex flex-col items-center justify-center hover:border-slate-500 transition">
                                <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                                <p class="text-sm text-slate-500">Klik atau drag foto di sini (PNG, JPG, JPEG) max 5MB</p>
                                <template x-if="form.fotoPreview">
                                    <img :src="form.fotoPreview" alt="Preview"
                                        class="mt-4 w-32 h-32 object-cover rounded-md border" />
                                </template>
                                <template x-if="!form.fotoPreview && form.fotoFileName">
                                    <img src="{{ $item->foto_path ? asset('storage/' . $item->foto_path) : '' }}"
                                        class="mt-4 w-32 h-32 object-cover rounded-md border" />
                                </template>
                                <template x-if="form.fotoFileName">
                                    <p class="mt-2 text-sm text-slate-600" x-text="form.fotoFileName"></p>
                                </template>
                            </div>
                            <input type="file" x-ref="fileInput" name="foto" @change="onFileChange($event)"
                                accept="image/png, image/jpeg, image/jpg" class="hidden" />
                        @else
                            {{-- View Only Mode untuk Foto --}}
                            <div class="border border-slate-200 rounded-lg p-6 flex flex-col items-center justify-center bg-slate-50">
                                @if($item->foto_path)
                                    <img src="{{ asset('storage/app/public/' . $item->foto_path) }}" alt="{{ $item->nama_item }}"
                                        class="w-32 h-32 object-cover rounded-md border" />
                                @else
                                    <div class="text-center text-slate-400">
                                        <i class="fa-solid fa-image text-3xl mb-2"></i>
                                        <p class="text-sm">Tidak ada foto</p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- STEP 2: MULTI SATUAN --}}
            <div x-show="currentStep === 1" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-ruler text-[#344579]"></i> Multi Satuan
                </h3>

                <input type="hidden" name="satuan_primary_index" x-model="primarySatuanIndex">

                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-1 md:grid-cols-8 gap-3 items-center">
                            <input type="hidden" :name="`satuans[${idx}][id]`" :value="s.id">

                            {{-- Nama satuan --}}
                            <div class="col-span-4">
                                <input :name="`satuans[${idx}][nama_satuan]`" x-model="s.nama_satuan" 
                                    {{ $canUpdate ? '' : 'disabled readonly' }}
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 {{ $canUpdate ? '' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}" />
                            </div>

                            {{-- Jumlah isi --}}
                            <div class="col-span-3">
                                <template x-if="!s.is_base">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-slate-600">Isi:</span>
                                        <input type="number" min="1" :name="`satuans[${idx}][jumlah]`"
                                            x-model.number="s.jumlah" {{ $canUpdate ? '' : 'disabled readonly' }}
                                            class="w-20 px-2 py-1 rounded-lg border border-slate-200 {{ $canUpdate ? '' : 'bg-slate-50 text-slate-600 cursor-not-allowed' }}" />
                                        <span class="text-sm text-slate-600" x-text="satuans[0]?.nama_satuan || ''"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Tombol hapus --}}
                            <div class="col-span-1 flex md:justify-end">
                                @if($canUpdate)
                                    <button type="button" @click="removeSatuan(idx)" class="text-rose-600 hover:text-rose-800"
                                        x-show="idx > 0">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    </template>

                    @if($canUpdate)
                        <div>
                            <button type="button" @click="addSatuan()"
                                class="px-4 py-2 rounded-lg bg-[#344579] hover:bg-[#2e3e6a] text-white w-full sm:w-auto">
                                <i class="fa-solid fa-plus mr-2"></i> Tambah Satuan
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- STEP 3: MULTI HARGA --}}
            <div x-show="currentStep === 2" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-tag text-[#344579]"></i> Multi Harga
                </h3>
                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Satuan</label>
                                <input type="text"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 cursor-not-allowed"
                                    x-model="s.nama_satuan" readonly disabled>
                                <input type="hidden" :name="`satuans[${idx}][nama_satuan]`" :value="s.nama_satuan">
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Retail <span class="text-rose-600">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text" {{ $canUpdate ? '' : 'disabled readonly' }}
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg py-2 text-sm text-right {{ $canUpdate ? '' : 'bg-slate-50 cursor-not-allowed' }}"
                                        :value="formatRupiah(s.harga_retail)"
                                        @input="updateHarga(idx,'harga_retail',$event.target.value)">
                                    <input type="hidden" :name="`satuans[${idx}][harga_retail]`" :value="s.harga_retail">
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Grosir</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text" {{ $canUpdate ? '' : 'disabled readonly' }}
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg py-2 text-sm text-right {{ $canUpdate ? '' : 'bg-slate-50 cursor-not-allowed' }}"
                                        :value="formatRupiah(s.harga_grosir)"
                                        @input="updateHarga(idx,'harga_grosir',$event.target.value)">
                                    <input type="hidden" :name="`satuans[${idx}][harga_grosir]`" :value="s.harga_grosir">
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Partai Kecil</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text" {{ $canUpdate ? '' : 'disabled readonly' }}
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg py-2 text-sm text-right {{ $canUpdate ? '' : 'bg-slate-50 cursor-not-allowed' }}"
                                        :value="formatRupiah(s.partai_kecil)"
                                        @input="updateHarga(idx,'partai_kecil',$event.target.value)">
                                    <input type="hidden" :name="`satuans[${idx}][partai_kecil]`" :value="s.partai_kecil">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-col sm:flex-row justify-end mt-4 gap-3">
                <a href="{{ route('items.index') }}"
                    class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-center">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
                </a>
                
                {{-- ✅ Tombol Simpan - Hanya muncul jika punya permission update --}}
                @can('items.update')
                    <button type="submit" :disabled="!canSubmit()"
                        :class="canSubmit() ? 'bg-[#334579] hover:bg-[#2d3e6f] text-white px-4 py-2 rounded-lg w-full sm:w-auto cursor-pointer' :
                            'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg w-full sm:w-auto'">
                        <i class="fa-solid fa-save mr-2"></i> Simpan Perubahan
                    </button>
                @endcan
            </div>
        </form>
    </div>

    {{-- SCRIPT ALPINE --}}
    <script>
        function itemsEdit() {
            return {
                steps: [
                    { title: 'Info Item' },
                    { title: 'Multi Satuan' },
                    { title: 'Multi Harga' }
                ],
                currentStep: 0,
                form: {
                    kode_item: '{!! old('kode_item', $item->kode_item) !!}',
                    nama_item: '{{ old('nama_item', $item->nama_item) }}',
                    stok_minimal: '{{ old('stok_minimal', $item->stok_minimal) }}',
                    kategori_item_id: '{{ old('kategori_item_id', $item->kategori_item_id) }}',
                    fotoPreview: null,
                    fotoFileName: '{{ $item->foto_path ? basename($item->foto_path) : '' }}',
                },
                satuans: [
                    @foreach ($item->satuans as $s)
                        {
                            id: {{ $s->id }},
                            uid: Date.now() + Math.random(),
                            nama_satuan: '{{ $s->nama_satuan }}',
                            jumlah: {{ $s->jumlah ?? 1 }},
                            is_base: {{ $s->is_base ? 'true' : 'false' }},
                            harga_retail: {{ $s->harga_retail ?? 0 }},
                            partai_kecil: {{ $s->partai_kecil ?? 0 }},
                            harga_grosir: {{ $s->harga_grosir ?? 0 }},
                        },
                    @endforeach
                ],
                initialData: {},
                primarySatuanIndex: {{ old('satuan_primary_index', $item->satuans->search(fn($s) => $s->is_base) ?? 0) }},

                init() {
                    this.initialData = JSON.parse(JSON.stringify({
                        form: this.form,
                        satuans: this.satuans
                    }));
                },

                formatRupiah(value) {
                    if (!value) return '';
                    let numberString = value.toString().replace(/[^,\d]/g, '');
                    let split = numberString.split(',');
                    let sisa = split[0].length % 3;
                    let rupiah = split[0].substr(0, sisa);
                    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

                    if (ribuan) {
                        let separator = sisa ? '.' : '';
                        rupiah += separator + ribuan.join('.');
                    }
                    rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
                    return rupiah;
                },

                updateHarga(idx, field, val) {
                    @if($canUpdate)
                        let number = val.replace(/[^0-9]/g, '');
                        this.satuans[idx][field] = parseInt(number || 0);
                        event.target.value = this.formatRupiah(number);
                    @endif
                },

                addSatuan() {
                    @if($canUpdate)
                        const base = this.satuans.find(s => s.is_base) || this.satuans[0];
                        this.satuans.push({
                            uid: Date.now() + Math.random(),
                            nama_satuan: '',
                            jumlah: 1,
                            is_base: false,
                            base_unit: base?.nama_satuan || 'PCS',
                            harga_retail: 0,
                            partai_kecil: 0,
                            harga_grosir: 0
                        });
                    @endif
                },

                removeSatuan(i) {
                    @if($canUpdate)
                        this.satuans.splice(i, 1);
                    @endif
                },

                onFileChange(e) {
                    @if($canUpdate)
                        const file = e.target.files[0];
                        if (!file) {
                            this.form.fotoPreview = null;
                            this.form.fotoFileName = null;
                            return;
                        }
                        if (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type)) return;
                        if (file.size > 5 * 1024 * 1024) return;
                        this.form.fotoPreview = URL.createObjectURL(file);
                        this.form.fotoFileName = file.name;
                    @endif
                },

                isChanged() {
                    const current = JSON.stringify({ form: this.form, satuans: this.satuans });
                    return current !== JSON.stringify(this.initialData);
                },

                canSubmit() {
                    @if(!$canUpdate)
                        return false;
                    @endif
                    
                    if (!this.form.kode_item || !this.form.nama_item || !this.form.kategori_item_id) return false;
                    if (!this.satuans.length) return false;
                    for (let s of this.satuans) {
                        if (!s.nama_satuan || !s.nama_satuan.trim()) return false;
                        if (s.harga_retail === '' || isNaN(Number(s.harga_retail))) return false;
                    }
                    return this.isChanged();
                }
            }
        }
    </script>

@endsection