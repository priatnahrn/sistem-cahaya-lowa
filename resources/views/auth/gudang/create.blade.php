@extends('layouts.app')

@section('title', 'Tambah Gudang Baru')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        form: {
            kode_gudang: '{{ old('kode_gudang', $newCode) }}',
            nama_gudang: '{{ old('nama_gudang') }}',
            lokasi: '{{ old('lokasi') }}'
        }
    }">


        <div>
            <a href="{{ route('gudang.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <form action="{{ route('gudang.store') }}" method="POST" class="space-y-4 w-full">
                @csrf

                {{-- Kode Gudang --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Kode Gudang</label>
                    <input name="kode_gudang" x-model="form.kode_gudang"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Contoh: GD-00001" />
                    @error('kode_gudang')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nama Gudang --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nama Gudang</label>
                    <input name="nama_gudang" x-model="form.nama_gudang"
                        class="w-full px-3 py-2 rounded-lg border border-slate-200" placeholder="Nama gudang" />
                    @error('nama_gudang')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lokasi --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Lokasi</label>
                    <input name="lokasi" x-model="form.lokasi" class="w-full px-3 py-2 rounded-lg border border-slate-200"
                        placeholder="Lokasi gudang" />
                    @error('lokasi')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('gudang.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Batal
                    </a>
                    <button type="submit" :disabled="!form.kode_gudang || !form.nama_gudang || !form.lokasi"
                        :class="(!form.kode_gudang || !form.nama_gudang || !form.lokasi) ?
                        'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' :
                        'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg'">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
