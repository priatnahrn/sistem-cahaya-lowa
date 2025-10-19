@extends('layouts.app')

@section('title', 'Detail Pelanggan')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        original: {
            nama_pelanggan: '{{ $pelanggan->nama_pelanggan }}',
            kontak: '{{ $pelanggan->kontak ?? '' }}',
            alamat: '{{ $pelanggan->alamat ?? '' }}',
            level: '{{ $pelanggan->level ?? '' }}',
        },
        form: {
            nama_pelanggan: '{{ $pelanggan->nama_pelanggan }}',
            kontak: '{{ $pelanggan->kontak ?? '' }}',
            alamat: '{{ $pelanggan->alamat ?? '' }}',
            level: '{{ $pelanggan->level ?? '' }}',
        },
        get isChanged() {
            return this.form.nama_pelanggan !== this.original.nama_pelanggan ||
                this.form.kontak !== this.original.kontak ||
                this.form.alamat !== this.original.alamat ||
                this.form.level !== this.original.level;
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
            <form action="{{ route('pelanggan.update', $pelanggan->id) }}" method="POST" class="space-y-4 w-full">
                @csrf
                @method('PUT')

                {{-- Nama Pelanggan --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nama Pelanggan</label>
                    <input name="nama_pelanggan" x-model="form.nama_pelanggan"
                        @cannot('pelanggan.update') disabled readonly @endcannot
                        class="w-full px-3 py-2 rounded-lg border border-slate-200
                           @cannot('pelanggan.update') bg-slate-50 cursor-not-allowed @endcannot"
                        placeholder="Contoh: PT. Maju Sejahtera" />
                    @error('nama_pelanggan')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kontak --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Kontak</label>
                    <input name="kontak" x-model="form.kontak" @cannot('pelanggan.update') disabled readonly @endcannot
                        class="w-full px-3 py-2 rounded-lg border border-slate-200
                           @cannot('pelanggan.update') bg-slate-50 cursor-not-allowed @endcannot"
                        placeholder="08xx-xxxx-xxxx / +62xxx" />
                    @error('kontak')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Alamat --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Alamat</label>
                    <textarea name="alamat" x-model="form.alamat" rows="3" @cannot('pelanggan.update') disabled readonly @endcannot
                        class="w-full px-3 py-2 rounded-lg border border-slate-200
                           @cannot('pelanggan.update') bg-slate-50 cursor-not-allowed @endcannot"
                        placeholder="Alamat lengkap (mis: Jl. Sudirman No. 10, Jakarta)"></textarea>
                    @error('alamat')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Level --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Level</label>
                    <div class="relative">
                        <select name="level" x-model="form.level" @cannot('pelanggan.update') disabled @endcannot
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 appearance-none pr-8
                               @cannot('pelanggan.update') bg-slate-50 cursor-not-allowed @endcannot">
                            <option value="">-- Pilih Level --</option>
                            <option value="retail">Retail</option>
                            <option value="partai_kecil">Partai Kecil</option>
                            <option value="grosir">Grosir</option>
                        </select>
                        {{-- Custom Arrow --}}
                        <div class="absolute inset-y-0 right-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                </path>
                            </svg>
                        </div>
                    </div>
                    @error('level')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('pelanggan.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Kembali
                    </a>
                    @can('pelanggan.update')
                        <button type="submit" :disabled="!isChanged"
                            :class="(!isChanged) ?
                            'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' :
                            'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg transition'">
                            <i class="fa-solid fa-save mr-1.5"></i>
                            Simpan Perubahan
                        </button>
                    @endcan
                </div>
            </form>
        </div>
    </div>
@endsection
