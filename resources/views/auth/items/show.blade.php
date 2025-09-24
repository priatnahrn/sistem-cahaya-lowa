@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
    <div class="space-y-6 w-full" x-data="itemsEdit()" x-init="init()">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('items.index') }}" class="text-slate-500 hover:underline text-sm">Item</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Edit Item
                </span>
            </div>
        </div>

        {{-- TAB HEADER --}}
        <div class="bg-white border border-slate-200 rounded-xl p-4 flex gap-6">
            <template x-for="(step, i) in steps" :key="i">
                <button @click="currentStep = i"
                    :class="currentStep === i ? 'text-[#0f766e] border-b-2 border-[#0f766e]' : 'text-slate-600'"
                    class="pb-2 text-sm font-medium">
                    <span x-text="step.title"></span>
                </button>
            </template>
        </div>

        {{-- FORM --}}
        <form :action="'{{ route('items.update', $item->id) }}'" method="POST" enctype="multipart/form-data" class="space-y-6" @submit.prevent="submit()">
            @csrf
            @method('PUT')

            {{-- hidden primary satuan index --}}
            <input type="hidden" name="satuan_primary_index" x-model="primarySatuanIndex">

            {{-- STEP 1: INFO ITEM --}}
            <div x-show="currentStep === 0" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Info Item</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kode Item <span class="text-rose-600">*</span></label>
                        <input name="kode_item" x-model="form.kode_item"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]"
                            placeholder="Contoh: ITM-20250920-001" />
                        <p x-show="errors.kode_item" class="text-rose-600 text-sm mt-1" x-text="errors.kode_item"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Item <span class="text-rose-600">*</span></label>
                        <input name="nama_item" x-model="form.nama_item"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]" placeholder="Nama item" />
                        <p x-show="errors.nama_item" class="text-rose-600 text-sm mt-1" x-text="errors.nama_item"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kategori Item <span class="text-rose-600">*</span></label>
                        <select name="kategori_item_id" x-model="form.kategori_item_id"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategori_items ?? [] as $k)
                                <option value="{{ $k->id }}" @if(($item->kategori_id ?? data_get($item,'kategori.id')) == $k->id) selected @endif>{{ $k->nama_kategori }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.kategori_item_id" class="text-rose-600 text-sm mt-1" x-text="errors.kategori_item_id"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Gudang <span class="text-rose-600">*</span></label>
                        <select name="gudang_id" x-model="form.gudang_id"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($gudangs ?? [] as $g)
                                <option value="{{ $g->id }}" @if(($item->gudang_id ?? data_get($item,'gudang.id')) == $g->id) selected @endif>{{ $g->nama_gudang ?? $g->nama }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.gudang_id" class="text-rose-600 text-sm mt-1" x-text="errors.gudang_id"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Stok Minimal</label>
                        <input type="number" min="0" name="stok_minimal" x-model.number="form.stok_minimal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]" />
                    </div>

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

                            <template x-if="form.fotoFileName && !form.fotoPreview">
                                <img :src="existingFotoUrl" alt="Existing Foto" class="mt-4 w-32 h-32 object-cover rounded-md border" />
                            </template>

                            <template x-if="form.fotoFileName">
                                <p class="mt-2 text-sm text-slate-600" x-text="form.fotoFileName"></p>
                            </template>
                        </div>

                        <input type="file" x-ref="fileInput" name="foto" @change="onFileChange($event)"
                            accept="image/png, image/jpeg, image/jpg" class="hidden" />
                        <p x-show="errors.foto" class="text-rose-600 text-sm mt-1" x-text="errors.foto"></p>
                    </div>
                </div>
            </div>

            {{-- STEP 2: MULTI SATUAN --}}
            <div x-show="currentStep === 1" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Multi Satuan</h3>

                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-12 gap-3 items-center">

                            {{-- Nama Satuan --}}
                            <div class="col-span-3">
                                <input :name="`satuans[${idx}][nama_satuan]`" x-model="s.nama_satuan"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]"
                                    placeholder="Contoh: PCS, LUSIN, DUS" />
                            </div>

                            {{-- Jumlah isi (hanya jika bukan base unit) --}}
                            <div class="col-span-4">
                                <template x-if="!s.is_base">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm text-slate-600">Isi:</span>
                                        <input type="number" min="1" :name="`satuans[${idx}][jumlah]`"
                                            x-model.number="s.jumlah"
                                            class="w-20 px-2 py-1 rounded-lg border border-slate-200 focus:ring-1 focus:ring-[#344579] focus:border-[#344579]" />
                                        <span class="text-sm text-slate-600" x-text="satuans[0]?.nama_satuan || '' "></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Pilih base --}}
                            <div class="col-span-2">
                                <label class="text-sm text-slate-600">Utama</label>
                                <div>
                                    <input type="radio" :name="'base_satuan'" x-on:click="setBase(idx)" :checked="s.is_base" class="w-4 h-4" />
                                    <input type="hidden" :name="`satuans[${idx}][is_base]`" x-bind:value="s.is_base ? 1 : 0" />
                                </div>
                            </div>

                            {{-- Hapus --}}
                            <div class="col-span-3 flex justify-end">
                                <button type="button" @click="removeSatuan(idx)"
                                    class="text-rose-600 hover:text-rose-800" x-show="idx > 0">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Tambah --}}
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
                <p class="text-sm text-slate-500 mb-4">Harga retail & grosir akan otomatis mengikuti daftar satuan.</p>

                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-12 gap-3 items-end">
                            {{-- Nama Satuan --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Satuan</label>
                                <input type="text"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-gray-50"
                                    x-model="s.nama_satuan" :value="s.nama_satuan" readonly />
                                <input type="hidden" :name="`satuans[${idx}][nama_satuan]`" :value="s.nama_satuan">
                            </div>

                            {{-- Harga Retail --}}
                            <div class="col-span-3">
                                <label class="block text-sm text-slate-600 mb-1">Harga Retail <span class="text-rose-600">*</span></label>
                                <input :name="`satuans[${idx}][harga_retail]`" x-model="s.harga_retail" type="number"
                                    min="0" step="0.01"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200"
                                    placeholder="Contoh: 15000" />
                            </div>

                            {{-- Harga Grosir --}}
                            <div class="col-span-4">
                                <label class="block text-sm text-slate-600 mb-1">Harga Grosir</label>
                                <input :name="`satuans[${idx}][harga_grosir]`" x-model="s.harga_grosir" type="number"
                                    min="0" step="0.01"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200"
                                    placeholder="Contoh: 12000" />
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- SIMPAN --}}
            <div class="flex justify-end mt-4">
                <a href="{{ route('items.index') }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 mr-3">Kembali</a>
                <button type="submit" :disabled="!canSubmit()"
                    :class="canSubmit() ? 'bg-[#0f766e] hover:bg-[#0e6a63] text-white px-4 py-2 rounded-lg' :
                        'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg'">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    {{-- Alpine JS --}}
    <script>
        function itemsEdit() {
            return {
                steps: [{ title: 'Info Item' }, { title: 'Multi Satuan' }, { title: 'Multi Harga' }],
                currentStep: 0,
                form: {
                    kode_item: '{{ addslashes(old('kode_item', $item->kode_item ?? $item->kode ?? '')) }}',
                    nama_item: '{{ addslashes(old('nama_item', $item->nama_item ?? $item->nama ?? '')) }}',
                    kategori_item_id: '{{ old('kategori_item_id', $item->kategori_id ?? data_get($item,'kategori.id') ?? '') }}',
                    gudang_id: '{{ old('gudang_id', $item->gudang_id ?? data_get($item,'gudang.id') ?? '') }}',
                    stok_minimal: {{ old('stok_minimal', $item->stok_minimal ?? $item->stok ?? 0) }},
                    fotoPreview: null,
                    fotoFileName: null,
                },
                existingFotoUrl: '{{ $item->foto_path ? asset("storage/{$item->foto_path}") : '' }}',
                satuans: [],
                primarySatuanIndex: {{ old('satuan_primary_index', 0) }},
                errors: {},

                init() {
                    // load satuans: prefer old() (failed validation), otherwise load from item
                    @if(old('satuans'))
                        this.satuans = [
                            @foreach(old('satuans') as $idx => $s)
                                {
                                    uid: Date.now() + {{ $idx }},
                                    nama_satuan: '{{ addslashes($s['nama_satuan'] ?? '') }}',
                                    jumlah: {{ $s['jumlah'] ?? 1 }},
                                    is_base: {{ (isset($s['is_base']) && $s['is_base']) ? 'true' : 'false' }},
                                    base_unit: '{{ addslashes($s['base_unit'] ?? '') }}',
                                    harga_retail: '{{ $s['harga_retail'] ?? '' }}',
                                    harga_grosir: '{{ $s['harga_grosir'] ?? '' }}',
                                },
                            @endforeach
                        ];
                    @else
                        // load from $item->satuans
                        this.satuans = [
                            @if($item->satuans && $item->satuans->count())
                                @foreach($item->satuans as $si)
                                    {
                                        uid: Date.now() + {{ $loop->index }},
                                        nama_satuan: '{{ addslashes($si->nama_satuan ?? $si->nama ?? '') }}',
                                        jumlah: {{ $si->jumlah ?? 1 }},
                                        is_base: {{ ($si->is_base ?? false) ? 'true' : 'false' }},
                                        base_unit: '{{ addslashes($si->base_unit ?? ($si->nama_satuan ?? $si->nama ?? '')) }}',
                                        harga_retail: '{{ $si->harga_retail ?? '' }}',
                                        harga_grosir: '{{ $si->harga_grosir ?? '' }}',
                                        id: {{ $si->id ?? 'null' }},
                                    }@if(!$loop->last),@endif
                                @endforeach
                            @else
                                // fallback single PCS if no satuans exist
                                {
                                    uid: Date.now(),
                                    nama_satuan: 'PCS',
                                    jumlah: 1,
                                    is_base: true,
                                    base_unit: 'PCS',
                                    harga_retail: '',
                                    harga_grosir: ''
                                }
                            @endif
                        ];
                    @endif

                    // errors from session
                    @if ($errors->any())
                        const errs = {};
                        @foreach ($errors->messages() as $k => $msgs)
                            errs['{{ $k }}'] = '{{ addslashes(implode(' ', $msgs)) }}';
                        @endforeach
                        this.errors = errs;
                    @endif

                    // set foto filename if existing
                    @if($item->foto_path)
                        this.form.fotoFileName = '{{ basename($item->foto_path) }}';
                    @endif
                },

                addSatuan() {
                    const base = this.satuans.find(s => s.is_base) || this.satuans[0] || { nama_satuan: 'PCS' };
                    this.satuans.push({
                        uid: Date.now() + Math.random(),
                        nama_satuan: '',
                        jumlah: 1,
                        is_base: false,
                        base_unit: base.nama_satuan,
                        harga_retail: '',
                        harga_grosir: ''
                    });
                },

                removeSatuan(i) {
                    const removed = this.satuans.splice(i, 1)[0];
                    if (removed && removed.is_base && this.satuans.length) {
                        this.satuans[0].is_base = true;
                        const newBase = this.satuans[0].nama_satuan;
                        this.satuans.forEach(s => s.base_unit = newBase);
                    }
                    if (this.primarySatuanIndex === i) this.primarySatuanIndex = 0;
                    else if (this.primarySatuanIndex > i) this.primarySatuanIndex--;
                },

                setBase(i) {
                    this.satuans.forEach((s, idx) => s.is_base = (idx === i));
                },

                onFileChange(e) {
                    const file = e.target.files[0];
                    if (!file) {
                        this.form.fotoPreview = null;
                        this.form.fotoFileName = null;
                        return;
                    }
                    const allowed = ['image/png', 'image/jpeg', 'image/jpg'];
                    if (!allowed.includes(file.type)) {
                        this.errors.foto = 'Format file tidak valid. Gunakan PNG/JPG/JPEG.';
                        this.form.fotoPreview = null;
                        this.form.fotoFileName = null;
                        e.target.value = '';
                        return;
                    }
                    const maxSize = 5 * 1024 * 1024;
                    if (file.size > maxSize) {
                        this.errors.foto = 'Ukuran file terlalu besar. Maksimum 5MB.';
                        this.form.fotoPreview = null;
                        this.form.fotoFileName = null;
                        e.target.value = '';
                        return;
                    }
                    this.errors.foto = null;
                    this.form.fotoPreview = URL.createObjectURL(file);
                    this.form.fotoFileName = file.name;
                },

                canSubmit() {
                    if (!this.form.kode_item || !this.form.nama_item || !this.form.kategori_item_id || !this.form.gudang_id)
                        return false;

                    if (this.satuans.length === 0 || this.satuans.some(s => !s.nama_satuan || !s.nama_satuan.trim().length))
                        return false;

                    if (this.satuans.some(s => s.harga_retail === '' || s.harga_retail === null || isNaN(Number(s.harga_retail))))
                        return false;

                    return true;
                },

                async submit() {
                    if (!this.canSubmit()) {
                        alert('Periksa kembali semua data sebelum menyimpan.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('_method', 'PUT');
                    fd.append('kode_item', this.form.kode_item);
                    fd.append('nama_item', this.form.nama_item);
                    fd.append('kategori_item_id', this.form.kategori_item_id);
                    fd.append('gudang_id', this.form.gudang_id);
                    fd.append('stok_minimal', this.form.stok_minimal ?? 0);

                    const fileInput = document.querySelector('input[name="foto"]');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        fd.append('foto', fileInput.files[0]);
                    }

                    // append satuans (includes harga_retail & harga_grosir & id)
                    this.satuans.forEach((s, i) => {
                        fd.append(`satuans[${i}][nama_satuan]`, s.nama_satuan ?? '');
                        fd.append(`satuans[${i}][jumlah]`, s.jumlah ?? 1);
                        fd.append(`satuans[${i}][base_unit]`, s.base_unit ?? '');
                        fd.append(`satuans[${i}][is_base]`, s.is_base ? 1 : 0);
                        fd.append(`satuans[${i}][harga_retail]`, (s.harga_retail !== undefined && s.harga_retail !== null) ? s.harga_retail : '');
                        fd.append(`satuans[${i}][harga_grosir]`, (s.harga_grosir !== undefined && s.harga_grosir !== null) ? s.harga_grosir : '');
                        if (s.id) fd.append(`satuans[${i}][id]`, s.id);
                    });

                    fd.append('satuan_primary_index', this.primarySatuanIndex ?? 0);

                    try {
                        const res = await fetch("{{ route('items.update', $item->id) }}", {
                            method: 'POST',
                            body: fd
                        });

                        if (res.redirected) {
                            window.location = res.url;
                            return;
                        }

                        if (res.status === 422) {
                            const js = await res.json();
                            const errs = {};
                            for (const k in js.errors) {
                                errs[k] = js.errors[k].join(' ');
                            }
                            this.errors = errs;
                            alert('Periksa kembali data yang error.');
                            return;
                        }

                        if (!res.ok) {
                            const text = await res.text().catch(() => null);
                            console.error('Server error:', text);
                            alert('Terjadi error saat mengirim form.');
                            return;
                        }

                        const js = await res.json().catch(() => null);
                        if (js && js.redirect) window.location = js.redirect;
                        else window.location = "{{ route('items.index') }}";
                    } catch (e) {
                        console.error(e);
                        alert('Gagal mengirim data. Cek console untuk detail.');
                    }
                }
            }
        }
    </script>
@endsection
