@extends('layouts.app')

@section('title', 'Detail Pengiriman')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>[x-cloak]{display:none!important}</style>

    <div x-data="pengirimanShowPage()" x-init="init()" class="space-y-6">

        {{-- Breadcrumb --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('pengiriman.index') }}" class="text-slate-500 hover:underline text-sm">Pengiriman</a>
            <div class="text-sm text-slate-400">/</div>
            <span class="px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 font-medium text-sm">
                {{ $pengiriman->no_pengiriman }}
            </span>
        </div>

        {{-- Card Info --}}
        <div class="bg-white border border-slate-200 rounded-xl px-6 py-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Pelanggan</label>
                <input type="text" readonly
                    value="{{ $pengiriman->penjualan->pelanggan->nama_pelanggan ?? 'Customer Umum' }}"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Faktur</label>
                    <input type="text" readonly value="{{ $pengiriman->penjualan->no_faktur }}"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">No Pengiriman</label>
                    <input type="text" readonly value="{{ $pengiriman->no_pengiriman }}"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Tanggal Pengiriman</label>
                    <input type="text" readonly value="{{ $pengiriman->tanggal_pengiriman }}"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />
                </div>
            </div>

            {{-- Dropdown Status --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Status Pengiriman</label>
                <select x-model="form.status_pengiriman" @change="checkChanged()"
                    class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500">
                    <option value="perlu_dikirim">🚚 Perlu Dikirim</option>
                    <option value="dalam_pengiriman">📦 Dalam Pengiriman</option>
                    <option value="diterima">✅ Diterima</option>
                    <option value="dibatalkan">❌ Dibatalkan</option>
                </select>
            </div>
        </div>

        {{-- Tabel Item --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-semibold text-slate-800">Daftar Item Penjualan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-slate-600">
                            <th class="px-4 py-3 text-center w-12">#</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3 text-center w-20">Jumlah</th>
                            <th class="px-4 py-3 text-center w-28">Satuan</th>
                            <th class="px-4 py-3 text-right w-32">Harga</th>
                            <th class="px-4 py-3 text-right w-40">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $subtotal = 0; @endphp
                        @foreach($pengiriman->penjualan->items as $idx => $it)
                            @php $rowTotal = $it->jumlah * $it->harga; $subtotal += $rowTotal; @endphp
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                <td class="px-4 py-3 text-center">{{ $idx+1 }}</td>
                                <td class="px-4 py-3">{{ $it->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">{{ $it->jumlah }}</td>
                                <td class="px-4 py-3 text-center">{{ $it->satuan->nama_satuan ?? '-' }}</td>
                                <td class="px-4 py-3 text-right">Rp {{ number_format($it->harga,0,',','.') }}</td>
                                <td class="px-4 py-3 text-right font-medium">Rp {{ number_format($rowTotal,0,',','.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="flex justify-end mt-4">
            <div class="w-full md:w-96 bg-white border border-slate-200 rounded-xl p-6 space-y-3">
                <div class="flex justify-between text-slate-600">
                    <span>Sub Total</span>
                    <span>Rp {{ number_format($subtotal,0,',','.') }}</span>
                </div>
                @if($pengiriman->penjualan->biaya_transport > 0)
                <div class="flex justify-between text-slate-600">
                    <span>Biaya Transportasi</span>
                    <span>Rp {{ number_format($pengiriman->penjualan->biaya_transport,0,',','.') }}</span>
                </div>
                @endif
                <div class="border-t pt-3 flex justify-between font-semibold text-lg text-slate-800">
                    <span>Total</span>
                    <span class="text-blue-700">Rp {{ number_format($subtotal + ($pengiriman->penjualan->biaya_transport ?? 0),0,',','.') }}</span>
                </div>
            </div>
        </div>

        {{-- Tombol --}}
        <div class="flex gap-3 justify-end">
            <a href="{{ route('pengiriman.index') }}"
                class="px-5 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50">
                Kembali
            </a>
            <button @click="updateStatus" type="button"
                :disabled="!changed"
                class="px-5 py-2.5 rounded-lg font-medium shadow-sm hover:shadow-md"
                :class="changed ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-300 text-slate-500 cursor-not-allowed'">
                <i class="fa-solid fa-save mr-2"></i>
                Simpan Perubahan
            </button>
        </div>
    </div>

    <script>
        function pengirimanShowPage() {
            return {
                form: {
                    id: {{ $pengiriman->id }},
                    status_pengiriman: '{{ $pengiriman->status_pengiriman }}',
                },
                originalStatus: '{{ $pengiriman->status_pengiriman }}',
                changed: false,

                init() {},

                checkChanged() {
                    this.changed = (this.form.status_pengiriman !== this.originalStatus);
                },

                async updateStatus() {
                    if (!this.changed) return;
                    try {
                        const res = await fetch(`/pengiriman/${this.form.id}/update-status`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ status_pengiriman: this.form.status_pengiriman })
                        });

                        if (res.ok) {
                            alert('Status pengiriman berhasil diperbarui!');
                            window.location.reload();
                        } else {
                            const err = await res.json();
                            alert('Gagal update status: ' + (err.message || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Terjadi kesalahan koneksi.');
                    }
                }
            }
        }
    </script>
@endsection
