@extends('layouts.app')

@section('title', 'Tambah Supplier Baru')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        form: {
            nama_supplier: '{{ addslashes(old('nama_supplier', '')) }}',
            kontak: '{{ addslashes(old('kontak', '')) }}',
            alamat: '{{ addslashes(old('alamat', '')) }}',
            nama_bank: '{{ addslashes(old('nama_bank', '')) }}',
            nomor_rekening: '{{ addslashes(old('nomor_rekening', '')) }}'
        }
    }">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('supplier.index') }}" class="text-slate-500 hover:underline text-sm">Supplier</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Tambah Supplier Baru
                </span>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <form action="{{ route('supplier.store') }}" method="POST" class="space-y-4 w-full">
                @csrf

                {{-- Nama Supplier --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nama Supplier</label>
                    <input name="nama_supplier" x-model="form.nama_supplier"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Contoh: PT. Sumber Jaya" />
                    @error('nama_supplier')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kontak / Nomor Telepon --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                    <input name="kontak" x-model="form.kontak" class="w-full px-3 py-2 rounded-lg border border-slate-200"
                        placeholder="08xx-xxxx-xxxx" />
                    @error('kontak')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat Lengkap --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Alamat Lengkap</label>
                    <textarea name="alamat" x-model="form.alamat" rows="3"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200"
                        placeholder="Alamat lengkap (mis: Jl. Merdeka No. 123, Bandung)"></textarea>
                    @error('alamat')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bank / Jenis Rekening -->
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Bank / Jenis Rekening</label>
                    <div class="relative">
                        <select name="nama_bank" x-model="form.nama_bank"
                            class="w-full px-3 py-2 pr-10 rounded-lg border border-slate-200 appearance-none">
                            <option value="">-- Pilih Bank --</option>
                            <option value="BCA" {{ old('nama_bank') === 'BCA' ? 'selected' : '' }}>BCA</option>
                            <option value="BNI" {{ old('nama_bank') === 'BNI' ? 'selected' : '' }}>BNI</option>
                            <option value="BRI" {{ old('nama_bank') === 'BRI' ? 'selected' : '' }}>BRI</option>
                            <option value="Mandiri" {{ old('nama_bank') === 'Mandiri' ? 'selected' : '' }}>Mandiri</option>
                            <option value="BSI" {{ old('nama_bank') === 'BSI' ? 'selected' : '' }}>BSI</option>
                            <option value="BTN" {{ old('nama_bank') === 'BTN' ? 'selected' : '' }}>BTN</option>
                            <option value="SMBC" {{ old('nama_bank') === 'SMBC' ? 'selected' : '' }}>SMBC</option>
                            <option value="Lainnya" {{ old('nama_bank') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>

                        <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                            ...>...</svg>
                    </div>

                    @error('nama_bank')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nomor Rekening -->
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nomor Rekening</label>
                    <input name="nomor_rekening" x-model="form.nomor_rekening" value="{{ old('nomor_rekening') }}"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Contoh: 1234567890" />
                    @error('nomor_rekening')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>


                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('supplier.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>

                    <button type="submit"
                        :disabled="!form.nama_supplier || !form.kontak || !form.alamat || !form.nama_bank || !form.nomor_rekening"
                        :class="(!form.nama_supplier || !form.kontak || !form.alamat || !form.nama_bank || !form
                        .nomor_rekening) ?
                        'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' :
                        'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
