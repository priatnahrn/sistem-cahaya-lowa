@extends('layouts.app')

@section('title', 'Detail Kategori Item')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        original: {
            nama_kategori: '{{ $category->nama_kategori }}',
            deskripsi: '{{ $category->deskripsi ?? '' }}'
        },
        form: {
            nama_kategori: '{{ $category->nama_kategori }}',
            deskripsi: '{{ $category->deskripsi ?? '' }}'
        },
        get isChanged() {
            return this.form.nama_kategori !== this.original.nama_kategori ||
                this.form.deskripsi !== this.original.deskripsi;
        }
    }">

        {{-- Breadcrumb / Back Button --}}
        <div>
            <a href="{{ route('items.categories.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
        </div>

        {{-- DETAIL CARD --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <form action="{{ route('items.categories.update', $category->id) }}" method="POST" class="space-y-4 w-full">
                @csrf
                @method('PUT')

                {{-- Nama Kategori --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Nama Kategori</label>
                    <input type="text" name="nama_kategori" x-model="form.nama_kategori"
                        @cannot('kategori_items.update') disabled readonly @endcannot
                        class="w-full px-3 py-2 rounded-lg border border-slate-200 
                               @cannot('kategori_items.update') bg-slate-50 cursor-not-allowed @endcannot"
                        placeholder="Nama kategori" />
                    @error('nama_kategori')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Deskripsi --}}
                <div>
                    <label class="block text-sm text-slate-600 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" x-model="form.deskripsi" rows="4"
                        @cannot('kategori_items.update') disabled readonly @endcannot
                        class="w-full px-3 py-2 rounded-lg border border-slate-200
                               @cannot('kategori_items.update') bg-slate-50 cursor-not-allowed @endcannot"
                        placeholder="Deskripsi kategori (opsional)"></textarea>
                    @error('deskripsi')
                        <p class="text-rose-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Actions --}}
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('items.categories.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 transition">
                        Kembali
                    </a>

                    {{-- Tombol Simpan hanya muncul jika punya permission update --}}
                    @can('kategori_items.update')
                        <button type="submit" :disabled="!isChanged"
                            :class="(!isChanged) ?
                            'bg-slate-300 cursor-not-allowed text-white px-4 py-2 rounded-lg' :
                            'bg-[#344579] hover:bg-[#2e3f6a] text-white px-4 py-2 rounded-lg transition'">

                            Simpan Perubahan
                        </button>
                    @endcan
                </div>
            </form>
        </div>

        {{-- Info Box untuk User Read-Only --}}
        @cannot('kategori_items.update')
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
                <i class="fa-solid fa-info-circle text-blue-600 text-lg mt-0.5"></i>
                <div class="text-sm text-blue-700">
                    <p class="font-medium">Mode Tampilan</p>
                    <p class="mt-1">Anda hanya dapat melihat data ini. Hubungi administrator untuk melakukan perubahan.</p>
                </div>
            </div>
        @endcannot

        {{-- Item yang Menggunakan Kategori Ini --}}
        @if ($category->items->count() > 0)
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                    <h3 class="font-semibold text-slate-800">
                        Item yang Menggunakan Kategori Ini
                        <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">
                            {{ $category->items->count() }}
                        </span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="px-4 py-3 text-center w-[60px]">#</th>
                                <th class="px-4 py-3 text-left">Kode Item</th>
                                <th class="px-4 py-3 text-left">Nama Item</th>
                                <th class="px-4 py-3 text-right">Stok</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($category->items as $idx => $item)
                                <tr class="hover:bg-slate-50 border-b border-slate-100">
                                    <td class="px-4 py-3 text-center text-slate-600">{{ $idx + 1 }}</td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-1 rounded">
                                            {{ $item->kode_item }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-800">
                                        {{ $item->nama_item }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        {{ number_format($item->stok ?? 0, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="bg-slate-50 border border-slate-200 rounded-xl p-8 text-center">
                <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-3"></i>
                <p class="text-slate-500">Belum ada item yang menggunakan kategori ini.</p>
            </div>
        @endif
    </div>
@endsection
