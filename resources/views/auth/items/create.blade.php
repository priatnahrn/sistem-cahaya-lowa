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
                <button @click="currentStep = i"
                    :class="currentStep === i ? 'text-[#0f766e] border-b-2 border-[#0f766e]' : 'text-slate-600'"
                    class="pb-2 text-sm font-medium">
                    <span x-text="step.title"></span>
                </button>
            </template>
        </div>

        {{-- FORM --}}
        <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6"
            @submit.prevent="submit()">
            @csrf

            {{-- STEP 1: INFO ITEM --}}
            <div x-show="currentStep === 0" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Info Item</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kode Item <span
                                class="text-rose-600">*</span></label>
                        <input name="kode_item" x-model="form.kode_item"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200"
                            placeholder="Contoh: ITM-20250920-001" />
                        <p x-show="errors.kode_item" class="text-rose-600 text-sm mt-1" x-text="errors.kode_item"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Item <span
                                class="text-rose-600">*</span></label>
                        <input name="nama_item" x-model="form.nama_item"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Nama item" />
                        <p x-show="errors.nama_item" class="text-rose-600 text-sm mt-1" x-text="errors.nama_item"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kategori Item <span
                                class="text-rose-600">*</span></label>
                        <select name="kategori_item_id" x-model="form.kategori_item_id"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach ($kategori_items ?? [] as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.kategori_item_id" class="text-rose-600 text-sm mt-1"
                            x-text="errors.kategori_item_id"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Gudang <span class="text-rose-600">*</span></label>
                        <select name="gudang_id" x-model="form.gudang_id"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($gudangs ?? [] as $g)
                                <option value="{{ $g->id }}">{{ $g->nama_gudang }}</option>
                            @endforeach
                        </select>
                        <p x-show="errors.gudang_id" class="text-rose-600 text-sm mt-1" x-text="errors.gudang_id"></p>
                    </div>

                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Stok Minimal</label>
                        <input type="number" min="0" name="stok_minimal" x-model.number="form.stok_minimal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" />
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
                <p class="text-sm text-slate-500 mb-4">Tambahkan satu atau lebih satuan untuk item ini.</p>

                <div class="space-y-3">
                    <template x-for="(s, idx) in satuans" :key="s.uid">
                        <div class="grid grid-cols-12 gap-3 items-center">
                            <div class="col-span-10">
                                <label class="block text-sm text-slate-600 mb-1">Nama Satuan <span
                                        class="text-rose-600">*</span></label>
                                <input :name="`satuans[${idx}][nama_satuan]`" x-model="s.nama_satuan"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200"
                                    placeholder="Contoh: PCS, BOX, SAK" />
                            </div>

                            <div class="col-span-2 flex items-end justify-end">
                                <button type="button" @click="removeSatuan(idx)"
                                    class="text-rose-600 hover:text-rose-800">
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

                    <div x-show="errors.satuans" class="text-rose-600 text-sm mt-2" x-text="errors.satuans"></div>
                </div>
            </div>

            {{-- STEP 3: MULTI HARGA --}}
            <div x-show="currentStep === 2" x-cloak class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <h3 class="text-lg font-semibold text-slate-700 mb-4">Multi Harga</h3>
                <p class="text-sm text-slate-500 mb-4">Tambahkan harga untuk kombinasi item + satuan.</p>

                <div class="space-y-3">
                    <template x-for="(h, idx) in hargas" :key="h.uid">
                        <div class="grid grid-cols-12 gap-3 items-end">
                            <div class="col-span-5">
                                <label class="block text-sm text-slate-600 mb-1">Satuan (untuk harga) <span
                                        class="text-rose-600">*</span></label>
                                <select :name="`hargas[${idx}][satuan_index]`" x-model.number="h.satuan_index"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-200">
                                    <option value="">-- Pilih Satuan --</option>
                                    <template x-for="(s, sidx) in satuans" :key="s.uid">
                                        <option :value="sidx" x-text="s.nama_satuan"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="col-span-5">
                                <label class="block text-sm text-slate-600 mb-1">Harga <span
                                        class="text-rose-600">*</span></label>
                                <input :name="`hargas[${idx}][harga]`" x-model="h.harga" type="number" min="0"
                                    step="0.01" class="w-full px-3 py-2 rounded-lg border border-slate-200"
                                    placeholder="Contoh: 15000" />
                            </div>

                            <div class="col-span-2 flex justify-end">
                                <button type="button" @click="removeHarga(idx)"
                                    class="text-rose-600 hover:text-rose-800">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div>
                        <button type="button" @click="addHarga()"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                            <i class="fa-solid fa-plus mr-2"></i> Tambah Harga
                        </button>
                    </div>

                    <div x-show="errors.hargas" class="text-rose-600 text-sm mt-2" x-text="errors.hargas"></div>
                </div>
            </div>

            {{-- SIMPAN --}}
            <div class="flex justify-end mt-4">
                <button type="submit" :disabled="!canSubmit()"
                    :class="canSubmit() ? 'bg-[#0f766e] hover:bg-[#0e6a63] text-white px-4 py-2 rounded-lg' :
                        'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg'">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    {{-- Alpine component --}}
    <script>
        function itemsWizard() {
            return {
                steps: [{
                        title: 'Info Item',
                        subtitle: 'Kode, nama & kategori'
                    },
                    {
                        title: 'Multi Satuan',
                        subtitle: 'Tambah satuan & pilih utama'
                    },
                    {
                        title: 'Multi Harga',
                        subtitle: 'Tambahkan harga per satuan'
                    },
                ],
                currentStep: 0,
                form: {
                    kode_item: '{{ old('kode_item', 'ITM-' . date('Ymd') . '-001') }}',
                    nama_item: '{{ old('nama_item') }}',
                    kategori_item_id: '{{ old('kategori_item_id') ?? '' }}',
                    gudang_id: '{{ old('gudang_id') ?? '' }}',
                    stok_minimal: {{ old('stok_minimal', 0) }},
                    fotoPreview: null,
                },
                satuans: [],
                primarySatuanIndex: {{ old('satuan_primary_index', 0) }},
                hargas: [],
                errors: {},

                init() {
                    @if (old('satuans'))
                        this.satuans = [
                            @foreach (old('satuans') as $idx => $s)
                                {
                                    uid: Date.now() + {{ $idx }},
                                    nama_satuan: {!! json_encode($s['nama_satuan']) !!}
                                },
                            @endforeach
                        ];
                    @else
                        this.satuans = [{
                            uid: Date.now(),
                            nama_satuan: '{{ old('satuans.0.nama_satuan', 'PCS') }}'
                        }];
                    @endif

                    @if (old('hargas'))
                        this.hargas = [
                            @foreach (old('hargas') as $idx => $h)
                                {
                                    uid: Date.now() + {{ $idx }} + 100,
                                    satuan_index: {{ $h['satuan_index'] ?? 'null' }},
                                    harga: '{{ $h['harga'] ?? '' }}'
                                },
                            @endforeach
                        ];
                    @else
                        this.hargas = [];
                    @endif

                    @if ($errors->any())
                        const errs = {};
                        @foreach ($errors->messages() as $k => $msgs)
                            errs['{{ $k }}'] = {!! json_encode(implode(' ', $msgs)) !!};
                        @endforeach
                        this.errors = errs;
                    @endif
                },

                addSatuan() {
                    this.satuans.push({
                        uid: Date.now() + Math.random(),
                        nama_satuan: ''
                    });
                },
                removeSatuan(i) {
                    this.satuans.splice(i, 1);
                    if (this.primarySatuanIndex === i) this.primarySatuanIndex = 0;
                    else if (this.primarySatuanIndex > i) this.primarySatuanIndex--;
                    this.hargas.forEach(h => {
                        if (h.satuan_index === i) h.satuan_index = null;
                        else if (h.satuan_index > i) h.satuan_index--;
                    });
                },

                addHarga() {
                    this.hargas.push({
                        uid: Date.now() + Math.random(),
                        satuan_index: null,
                        harga: ''
                    });
                },
                removeHarga(i) {
                    this.hargas.splice(i, 1);
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
                    this.form.fotoFileName = file.name; // simpan nama file untuk ditampilkan
                },



                canSubmit() {
                    // validasi saat submit
                    if (!this.form.kode_item || !this.form.nama_item || !this.form.kategori_item_id || !this.form.gudang_id)
                        return false;
                    if (this.satuans.length === 0 || this.satuans.some(s => !s.nama_satuan || !s.nama_satuan.trim().length))
                        return false;
                    if (this.hargas.length === 0) return false;
                    for (const h of this.hargas) {
                        if (h.satuan_index === null || h.satuan_index === '' || h.harga === '' || h.harga === null)
                            return false;
                        if (typeof this.satuans[h.satuan_index] === 'undefined') return false;
                    }
                    return true;
                },

                submit() {
                    if (!this.canSubmit()) {
                        alert('Periksa kembali semua data sebelum menyimpan.');
                        return;
                    }

                    const fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('kode_item', this.form.kode_item);
                    fd.append('nama_item', this.form.nama_item);
                    fd.append('kategori_item_id', this.form.kategori_item_id);
                    fd.append('gudang_id', this.form.gudang_id);
                    fd.append('stok_minimal', this.form.stok_minimal);

                    const fileInput = document.querySelector('input[name="foto"]');
                    if (fileInput && fileInput.files && fileInput.files[0]) {
                        fd.append('foto', fileInput.files[0]);
                    }

                    this.satuans.forEach((s, i) => {
                        fd.append(`satuans[${i}][nama_satuan]`, s.nama_satuan);
                    });
                    fd.append('satuan_primary_index', this.primarySatuanIndex);

                    this.hargas.forEach((h, i) => {
                        fd.append(`hargas[${i}][satuan_index]`, h.satuan_index);
                        fd.append(`hargas[${i}][harga]`, h.harga);
                    });

                    fetch("{{ route('items.store') }}", {
                        method: 'POST',
                        body: fd,
                    }).then(async res => {
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
                        } else if (!res.ok) {
                            alert('Terjadi error saat mengirim form.');
                        } else {
                            const js = await res.json().catch(() => null);
                            if (js && js.redirect) window.location = js.redirect;
                            else window.location = "{{ route('items.index') }}";
                        }
                    }).catch(e => {
                        console.error(e);
                        alert('Gagal mengirim data. Cek console untuk detail.');
                    });
                }
            }
        }
    </script>
@endsection
