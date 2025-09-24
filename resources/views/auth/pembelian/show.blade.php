@extends('layouts.app')

@section('title', 'Detail Pembelian')

@section('content')
    <div class="space-y-6 w-full" x-data="{
        original: {
            supplier_id: '{{ $pembelian->supplier_id ?? '' }}',
            no_faktur: '{{ $pembelian->no_faktur }}',
            tanggal: '{{ $pembelian->tanggal }}',
            deskripsi: '{{ $pembelian->deskripsi ?? '' }}',
            biaya_transport: '{{ $pembelian->biaya_transport ?? 0 }}',
            status: '{{ $pembelian->status }}',
        },
        form: {
            supplier_id: '{{ $pembelian->supplier_id ?? '' }}',
            no_faktur: '{{ $pembelian->no_faktur }}',
            tanggal: '{{ $pembelian->tanggal }}',
            deskripsi: '{{ $pembelian->deskripsi ?? '' }}',
            biaya_transport: '{{ $pembelian->biaya_transport ?? 0 }}',
            status: '{{ $pembelian->status }}',
        },
        get isChanged() {
            return JSON.stringify(this.original) !== JSON.stringify(this.form);
        }
    }">

        {{-- BREADCRUMB --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('pembelian.index') }}" class="text-slate-500 hover:underline text-sm">Pembelian</a>
            <div class="text-sm text-slate-400">/</div>
            <div class="inline-flex items-center text-sm">
                <span class="px-3 py-1 rounded-md bg-[#E9F3FF] text-[#1D4ED8] border border-[#BFDBFE] font-medium">
                    Detail Pembelian
                </span>
            </div>
        </div>

        {{-- DETAIL --}}
        <div class="bg-white border border-slate-200 rounded-xl p-6 w-full">
            <form action="{{ route('pembelian.update', $pembelian->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Supplier --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Supplier</label>
                        <select name="supplier_id" x-model="form.supplier_id"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200">
                            <option value="">Pilih Supplier</option>
                            @foreach ($suppliers as $s)
                                <option value="{{ $s->id }}"
                                    {{ $pembelian->supplier_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->nama_supplier }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Nomor Faktur --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Nomor Faktur</label>
                        <input name="no_faktur" x-model="form.no_faktur"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" readonly />
                    </div>

                    {{-- Tanggal --}}
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Tanggal</label>
                        <input type="date" name="tanggal" x-model="form.tanggal"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" />
                    </div>
                </div>

                {{-- Deskripsi + Transport + Status --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Deskripsi</label>
                        <input name="deskripsi" x-model="form.deskripsi"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600 mb-1">Biaya Transport</label>
                        <input name="biaya_transport" type="number" min="0" x-model="form.biaya_transport"
                            class="w-full px-3 py-2 rounded-lg border border-slate-200" />
                    </div>
                    <div class="flex items-center gap-2 mt-6">
                        <input type="checkbox" id="status" name="status" value="paid" x-model="form.status"
                            true-value="paid" false-value="unpaid" class="w-5 h-5 text-indigo-600 rounded border-gray-300">
                        <label for="status" class="text-sm text-slate-600">Lunas</label>
                    </div>
                </div>

                {{-- ITEM TABLE --}}
                <div class="overflow-x-auto mt-6">
                    <table class="min-w-full text-sm border">
                        <thead class="bg-slate-50 border-b">
                            <tr class="text-slate-600">
                                <th class="px-3 py-2">#</th>
                                <th class="px-3 py-2">Item</th>
                                <th class="px-3 py-2">Gudang</th>
                                <th class="px-3 py-2">Jumlah</th>
                                <th class="px-3 py-2">Satuan</th>
                                <th class="px-3 py-2 text-right">Harga</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pembelian->items as $idx => $it)
                                <tr class="border-b">
                                    <td class="px-3 py-2">{{ $idx + 1 }}</td>
                                    <td class="px-3 py-2">{{ $it->item->nama_item }}</td>
                                    <td class="px-3 py-2">{{ $it->gudang->nama_gudang }}</td>
                                    <td class="px-3 py-2 text-center">{{ $it->jumlah }}</td>
                                    <td class="px-3 py-2 text-center">{{ $it->satuan->nama_satuan }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format($it->harga, 0, ',', '.') }}</td>
                                    <td class="px-3 py-2 text-right">
                                        {{ number_format($it->jumlah * $it->harga, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- ACTIONS --}}
                <div class="flex justify-end gap-3 pt-4">
                    <a href="{{ route('pembelian.index') }}"
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
    </div>
@endsection
