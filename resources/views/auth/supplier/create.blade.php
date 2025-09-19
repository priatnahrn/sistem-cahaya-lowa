@extends('layouts.app')

@section('title','Tambah Supplier Baru')

@section('content')
<div class="space-y-6 w-full" x-data="{ 
        form: { 
            nama: '{{ old('nama') }}', 
            telepon: '{{ old('telepon') }}', 
            alamat: '{{ old('alamat') }}', 
            deskripsi: '{{ old('deskripsi') }}', 
            bank: '{{ old('bank') }}', 
            rekening: '{{ old('rekening') }}' 
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
        <form action="" method="POST" class="space-y-4 w-full">
            @csrf

            {{-- Nama Supplier --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nama Supplier</label>
                <input name="nama" x-model="form.nama"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="Contoh: PT. Sumber Jaya" />
                @error('nama')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nomor Telepon --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nomor Telepon</label>
                <input name="telepon" x-model="form.telepon"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="08xx-xxxx-xxxx" />
                @error('telepon')
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

            {{-- Deskripsi (opsional) --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Deskripsi (Opsional)</label>
                <textarea name="deskripsi" x-model="form.deskripsi" rows="2"
                          class="w-full px-3 py-2 rounded-lg border border-slate-200"
                          placeholder="Keterangan tambahan (opsional)"></textarea>
                @error('deskripsi')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Jenis Rekening / Nama Bank --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Bank / Jenis Rekening</label>
                <select name="bank" x-model="form.bank"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200">
                    <option value="">-- Pilih Bank --</option>
                    <option value="BCA">BCA</option>
                    <option value="BNI">BNI</option>
                    <option value="BRI">BRI</option>
                    <option value="Mandiri">Mandiri</option>
                    <option value="CIMB Niaga">CIMB Niaga</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
                @error('bank')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Nomor Rekening --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nomor Rekening</label>
                <input name="rekening" x-model="form.rekening"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="Contoh: 1234567890" />
                @error('rekening')
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
                        :disabled="!form.nama || !form.telepon || !form.alamat || !form.bank || !form.rekening"
                        :class="(!form.nama || !form.telepon || !form.alamat || !form.bank || !form.rekening) 
                                ? 'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' 
                                : 'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
