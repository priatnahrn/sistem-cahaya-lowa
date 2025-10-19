@extends('layouts.app')

@section('title', 'Detail Supplier')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        form: {
            nama_supplier: '{{ addslashes($supplier->nama_supplier) }}',
            kontak: '{{ addslashes($supplier->kontak) }}',
            alamat: '{{ addslashes($supplier->alamat) }}',
            nama_bank: '{{ addslashes($supplier->nama_bank) }}',
            nomor_rekening: '{{ addslashes($supplier->nomor_rekening) }}'
        },
        original: {
            nama_supplier: '{{ addslashes($supplier->nama_supplier) }}',
            kontak: '{{ addslashes($supplier->kontak) }}',
            alamat: '{{ addslashes($supplier->alamat) }}',
            nama_bank: '{{ addslashes($supplier->nama_bank) }}',
            nomor_rekening: '{{ addslashes($supplier->nomor_rekening) }}'
        },
        get isChanged() {
            return this.form.nama_supplier !== this.original.nama_supplier ||
                (this.form.kontak ?? '') !== (this.original.kontak ?? '') ||
                (this.form.alamat ?? '') !== (this.original.alamat ?? '') ||
                (this.form.nama_bank ?? '') !== (this.original.nama_bank ?? '') ||
                (this.form.nomor_rekening ?? '') !== (this.original.nomor_rekening ?? '');
        }
    }">

        {{-- BUTTON KEMBALI --}}
        <div>
            <a href="{{ route('supplier.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- ✅ PERMISSION CHECK: EDIT FORM atau VIEW ONLY --}}
        @can('supplier.update')
            {{-- EDIT MODE - User dengan permission update --}}
            <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                <form action="{{ route('supplier.update', $supplier->id) }}" method="POST" class="space-y-4 w-full">
                    @csrf
                    @method('PUT')

                    {{-- Nama Supplier --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Supplier</label>
                        <input name="nama_supplier" x-model="form.nama_supplier"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Contoh: PT. Sumber Jaya" />
                        @error('nama_supplier')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kontak --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                        <input name="kontak" x-model="form.kontak" class="w-full px-3 py-2 rounded-lg border border-slate-200"
                            placeholder="08xx-xxxx-xxxx" />
                        @error('kontak')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Alamat --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat" x-model="form.alamat" rows="3"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200"
                            placeholder="Alamat lengkap (mis: Jl. Merdeka No. 123, Bandung)"></textarea>
                        @error('alamat')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Bank --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Bank / Jenis Rekening</label>
                        <select name="nama_bank" x-model="form.nama_bank"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200">
                            <option value="">-- Pilih Bank --</option>
                            <option value="BCA">BCA</option>
                            <option value="BNI">BNI</option>
                            <option value="BRI">BRI</option>
                            <option value="Mandiri">Mandiri</option>
                            <option value="CIMB Niaga">CIMB Niaga</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        @error('nama_bank')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nomor Rekening --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nomor Rekening</label>
                        <input name="nomor_rekening" x-model="form.nomor_rekening"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Contoh: 1234567890" />
                        @error('nomor_rekening')
                            <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('supplier.index') }}"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                            Kembali
                        </a>
                        <button type="submit" :disabled="!isChanged"
                            :class="(!isChanged) ?
                            'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' :
                            'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        @else
            {{-- VIEW ONLY MODE - User tanpa permission update --}}
            <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
                {{-- Permission Warning --}}
                <div class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200 mb-6">
                    <i class="fa-solid fa-info-circle text-blue-600"></i>
                    <span class="text-sm text-blue-700">
                        Anda hanya memiliki akses untuk melihat data supplier. Hubungi administrator untuk melakukan perubahan.
                    </span>
                </div>

                <div class="space-y-4 w-full">
                    {{-- Nama Supplier --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nama Supplier</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                            {{ $supplier->nama_supplier }}
                        </div>
                    </div>

                    {{-- Kontak --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                            {{ $supplier->kontak ?? '-' }}
                        </div>
                    </div>

                    {{-- Alamat --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Alamat Lengkap</label>
                        <div
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700 whitespace-pre-wrap">
                            {{ $supplier->alamat ?? '-' }}
                        </div>
                    </div>

                    {{-- Nama Bank --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Bank / Jenis Rekening</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                            {{ $supplier->nama_bank ?? '-' }}
                        </div>
                    </div>

                    {{-- Nomor Rekening --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nomor Rekening</label>
                        <div class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 text-slate-700">
                            {{ $supplier->nomor_rekening ?? '-' }}
                        </div>
                    </div>

                    {{-- Actions - Hanya tombol kembali --}}
                    <div class="flex justify-end gap-3 pt-2">
                        <a href="{{ route('supplier.index') }}"
                            class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
        @endcan
    </div>
@endsection
