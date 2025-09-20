@extends('layouts.app')

@section('title','Tambah Kategori Item Baru')

@section('content')
<div class="space-y-6 w-full" x-data="{ 
        form: { 
            nama_kategori: '{{ old('nama_kategori') }}', 
        } 
    }">

    {{-- BREADCRUMB --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('items.categories.index') }}" class="text-slate-500 hover:underline text-sm">Kategori</a>
        <div class="text-sm text-slate-400">/</div>
        <div class="inline-flex items-center text-sm">
            <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                Tambah Kategori Item Baru
            </span>
        </div>
    </div>

    {{-- FORM CARD --}}
    <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
        <form action="{{ route('items.categories.store') }}" method="POST" class="space-y-4 w-full">
            @csrf

            {{-- Nama Kategori --}}
            <div>
                <label class="block text-sm text-slate-600 mb-1">Nama Kategori</label>
                <input name="nama_kategori" x-model="form.nama_kategori"
                       class="w-full px-3 py-2 rounded-lg border border-slate-200"
                       placeholder="Contoh: Elektronik" />
                @error('nama_kategori')
                    <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>


            {{-- Actions --}}
            <div class="flex justify-end gap-3 pt-2">
                <a href="{{ route('items.categories.index') }}" 
                   class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                        :disabled="!form.nama_kategori"
                        :class="(!form.nama_kategori) 
                                ? 'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' 
                                : 'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
