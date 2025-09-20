@extends('layouts.app')

@section('title','Detail Pelanggan')

@section('content')
<div class="space-y-6 w-full" x-data="{ 
        original: {
            nama_pelanggan: '{{ $pelanggan->nama_pelanggan }}',
            kontak: '{{ $pelanggan->kontak ?? '' }}',
            alamat: '{{ $pelanggan->alamat ?? '' }}'
        },
        form: {
            nama_pelanggan: '{{ $pelanggan->nama_pelanggan }}',
            kontak: '{{ $pelanggan->kontak ?? '' }}',
            alamat: '{{ $pelanggan->alamat ?? '' }}'
        },
        get isChanged() {
            return this.form.nama_pelanggan !== this.original.nama_pelanggan ||
                   this.form.kontak !== this.original.kontak ||
                   this.form.alamat !== this.original.alamat;
        }
    }">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('pelanggan.index') }}" class="text-slate-500 hover:underline text-sm">Pelanggan</a>
        <div class="text-sm text-slate-400">/</div>
        <div class="inline-flex items-center text-sm">
            <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                Detail Pelanggan
            </span>
        </div>
    </div>

    {{-- DETAIL CARD --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
        <form :action="'{{ route('pelanggan.update', $pelanggan->id) }}'" method="POST" class="space-y-4 w-full">
            @csrf
            @method('PUT')

            {{-- Nama Pelanggan --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nama Pelanggan</label>
                <input name="nama_pelanggan" x-model="form.nama_pelanggan"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="Contoh: PT. Maju Sejahtera" />
                @error('nama_pelanggan')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Kontak --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                <input name="kontak" x-model="form.kontak"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="08xx-xxxx-xxxx / +62xxx" />
                @error('kontak')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Alamat --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Alamat</label>
                <textarea name="alamat" x-model="form.alamat" rows="3"
                          class="w-full px-3 py-2 rounded-lg border border-slate-200"
                          placeholder="Alamat lengkap (mis: Jl. Sudirman No. 10, Jakarta)"></textarea>
                @error('alamat')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('pelanggan.index') }}" 
                   class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Kembali
                </a>
                <button type="submit"
                        :disabled="!isChanged"
                        :class="(!isChanged) 
                                ? 'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' 
                                : 'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
