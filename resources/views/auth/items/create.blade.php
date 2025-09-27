@extends('layouts.app')

@section('title', 'Tambah Item Baru')

@section('content')
    <div class="space-y-6 w-full" x-data="itemsWizard()" x-init="init()">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('items.index') }}" class="text-slate-500 hover:underline text-sm">Item</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Item Baru
                </span>
            </div>
        </div>

        {{-- TAB HEADER --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex gap-6">
            <template x-for="(step, i) in steps" :key="i">
                <button type="button" @click="currentStep = i"
                    :class="currentStep === i ? 'text-[#344579] border-b-2 border-[#344579]' : 'text-slate-600'"
                    class="pb-2 text-sm font-medium">
                    <span x-text="step.title"></span>
                </button>
            </template>
        </div>

        {{-- FORM --}}
        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <input type="hidden" name="satuan_primary_index" x-model="primarySatuanIndex">

            {{-- STEP 1: INFO ITEM --}}
            <div x-show="currentStep === 0" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Info Item</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- kode item preview --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">
                            Kode Item
                        </label>
                        <input type="text"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-500"
                            value="" placeholder="Kode item">
                        <p class="text-xs text-slate-400 mt-1">
                            Bila tidak diisi, kode item akan dibuat otomatis berdasarkan kategori yang dipilih dan akan
                            muncul setelah
                            disimpan.
                        </p>
                    </div>

                    {{-- nama item --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Item <span
                                class="text-rose-600">*</span></label>
                        <input name="nama_item" x-model="form.nama_item"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Nama item" />
                    </div>

                    {{-- kategori item --}}
                    <div class="relative w-full">
                        <label class="block text-sm text-slate-600 mb-1">Kategori Item <span
                                class="text-rose-600">*</span></label>
                        <select name="kategori_item_id" x-model="form.kategori_item_id"
                            class="w-full border border-gray-300 rounded-lg pl-3 pr-10 text-sm bg-white appearance-none h-11 leading-none focus:ring-2 focus:ring-[#0f766e] focus:border-[#0f766e]">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategori_items ?? [] as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kategori }}</option>
                            @endforeach
                        </select>

                        {{-- icon dropdown - CORRECTED POSITION --}}
                        <div class="absolute right-3 top-6 flex items-center h-11 pointer-events-none">
                            <i class="fa-solid fa-chevron-down text-slate-400 text-xs"></i>
                        </div>
                    </div>


                    {{-- foto --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Foto Item</label>
                        <div @click="$refs.fileInput.click()"
                            class="cursor-pointer border-2 border-dashed border-slate-300 rounded-lg p-6 flex flex-col items-center justify-center hover:border-slate-500 transition">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-slate-400 mb-2"></i>
                            <p class="text-sm text-slate-500">Klik atau drag foto di sini (PNG, JPG, JPEG) max 5MB</p>
                            <template x-if="form.fotoPreview">
                                <img :src="form.fotoPreview" alt="Preview"
                                    class="mt-4 w-32 h-32 object-cover rounded-md border" />
                            </template>
                            <template x-if="form.fotoFileName">
                                <p class="mt-2 text-sm text-slate-600" x-text="form.fotoFileName"></p>
                            </template>
                        </div>
                        <input type="file" x-ref="fileInput" name="foto" @change="onFileChange($event)"
                            accept="image/png, image/jpeg, image/jpg" class="hidden" />
                    </div>
                </div>
            </div>

            {{-- STEP 2: MULTI SATUAN --}}
            <div x-show="currentStep === 1" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Multi Satuan</h3>
                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-8 gap-3 items-center">
                            <div class="col-span-4">
                                <input :name="`satuans[${idx}][nama_satuan]`" x-model="s.nama_satuan"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200"
                                    placeholder="Contoh: Lusin, Dus, dan lain-lainnya" />
                            </div>
                            <div class="col-span-3">
                                <template x-if="!s.is_base">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-slate-600">Jumlah</span>
                                        <input type="number" min="1" :name="`satuans[${idx}][jumlah]`"
                                            x-model.number="s.jumlah"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-200" />
                                        <span class="text-sm text-slate-600" x-text="satuans[0]?.nama_satuan || '' "></span>
                                    </div>
                                </template>
                            </div>
                            <div class="col-span-1 flex justify-end">
                                <button type="button" @click="removeSatuan(idx)" class="text-rose-600 hover:text-rose-800"
                                    x-show="idx > 0">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                    <div>
                        <button type="button" @click="addSatuan()"
                            class="px-4 py-2 rounded-lg bg-[#344579] hover:bg-[#2e3e6a] text-white">
                            <i class="fa-solid fa-plus mr-2"></i> Tambah Satuan
                        </button>
                    </div>
                </div>
            </div>

            {{-- STEP 3: MULTI HARGA --}}
            <div x-show="currentStep === 2" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Multi Harga</h3>

                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-12 gap-3 items-end">
                            {{-- Nama Satuan --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Satuan</label>
                                <input type="text"
                                    class="w-full px-2 py-1.5 rounded-lg border border-slate-200 bg-gray-50"
                                    x-model="s.nama_satuan" readonly />
                                <input type="hidden" :name="`satuans[${idx}][nama_satuan]`" :value="s.nama_satuan">
                            </div>

                            {{-- Harga Retail --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Retail <span class="text-rose-600">*</span></label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text"
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg  py-2 text-sm text-right"
                                        :value="formatRupiah(s.harga_retail)"
                                        @input="updateHarga(idx, 'harga_retail', $event.target.value)" />
                                    <input type="hidden" :name="`satuans[${idx}][harga_retail]`"
                                        :value="s.harga_retail">
                                </div>
                            </div>

                            {{-- Harga Partai Kecil --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Partai Kecil</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text"
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg py-2 text-sm text-right"
                                        :value="formatRupiah(s.partai_kecil)"
                                        @input="updateHarga(idx, 'partai_kecil', $event.target.value)" />
                                    <input type="hidden" :name="`satuans[${idx}][partai_kecil]`"
                                        :value="s.partai_kecil">
                                </div>
                            </div>

                            {{-- Harga Grosir --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Grosir</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-sm">Rp</span>
                                    <input type="text"
                                        class="pl-10 pr-3 w-full border border-slate-200 rounded-lg py-2 text-sm text-right"
                                        :value="formatRupiah(s.harga_grosir)"
                                        @input="updateHarga(idx, 'harga_grosir', $event.target.value)" />
                                    <input type="hidden" :name="`satuans[${idx}][harga_grosir]`"
                                        :value="s.harga_grosir">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>


            {{-- BATAL & SIMPAN --}}
            <div class="flex justify-end gap-4 mt-4">
                <a href="{{ route('items.index') }}" 
                   class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit" :disabled="!canSubmit()"
                    :class="canSubmit() ?
                        'bg-[#344579] hover:bg-[#2d3e6f] text-white px-4 py-2 rounded-lg' :
                        'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg'">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    <script>
        function itemsWizard() {
            return {
                steps: [{
                        title: 'Info Item'
                    },
                    {
                        title: 'Multi Satuan'
                    },
                    {
                        title: 'Multi Harga'
                    },
                ],
                currentStep: 0,
                form: {
                    nama_item: '{{ old('nama_item') }}',
                    kategori_item_id: '{{ old('kategori_item_id') ?? '' }}',
                    stok_minimal: {{ old('stok_minimal', 0) }},
                    fotoPreview: null,
                },
                satuans: [{
                    uid: Date.now(),
                    nama_satuan: 'PCS',
                    jumlah: 1,
                    is_base: true,
                    base_unit: 'PCS',
                    harga_retail: '',
                    partai_kecil: '',
                    harga_grosir: ''
                }],
                primarySatuanIndex: {{ old('satuan_primary_index', 0) }},

                // 🔹 method dipindahkan ke dalam object
                formatRupiah(value) {
                    if (!value) return '';
                    let numberString = value.toString().replace(/[^,\d]/g, ''),
                        split = numberString.split(','),
                        sisa = split[0].length % 3,
                        rupiah = split[0].substr(0, sisa),
                        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

                    if (ribuan) {
                        let separator = sisa ? '.' : '';
                        rupiah += separator + ribuan.join('.');
                    }
                    rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
                    return rupiah;
                },
                updateHarga(idx, field, val) {
                    let number = val.replace(/[^0-9]/g, ''); // ambil angka aja
                    this.satuans[idx][field] = number; // simpan numeric ke hidden
                    event.target.value = this.formatRupiah(number); // update tampilan
                },

                addSatuan() {
                    const base = this.satuans.find(s => s.is_base) || this.satuans[0];
                    this.satuans.push({
                        uid: Date.now() + Math.random(),
                        nama_satuan: '',
                        jumlah: 1,
                        is_base: false,
                        base_unit: base.nama_satuan,
                        harga_retail: '',
                        partai_kecil: '',
                        harga_grosir: ''
                    });
                },
                removeSatuan(i) {
                    this.satuans.splice(i, 1);
                },
                onFileChange(e) {
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
                },

                // validasi tombol simpan
                canSubmit() {
                    if (!this.form.nama_item || !this.form.nama_item.trim()) return false;
                    if (!this.form.kategori_item_id) return false;
                    if (!this.satuans.length) return false;

                    for (let s of this.satuans) {
                        if (!s.nama_satuan || !s.nama_satuan.trim()) return false;
                        if (s.harga_retail === '' || s.harga_retail === null || isNaN(Number(s.harga_retail)))
                            return false;
                    }
                    return true;
                }
            }
        }
    </script>

@endsection
