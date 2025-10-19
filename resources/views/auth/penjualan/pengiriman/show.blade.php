@extends('layouts.app')

@section('title', 'Detail Pengiriman')

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        [x-cloak] {
            display: none !important
        }


        /* Pastikan dropdown mengikuti lebar parent */
        .dropdown-fixed {
            width: 100%;
        }

        /* Untuk browser modern (Chrome, Edge, Safari, Firefox) */
        .dropdown-fixed::-webkit-scrollbar {
            width: 8px;
        }

        /* Paksakan dropdown list tetap sejajar */
        .dropdown-fixed option {
            white-space: nowrap;
        }

        /* Chrome/Edge sometimes overflow fix */
        select.dropdown-fixed {
            text-overflow: ellipsis;
        }

        /* Optional: force dropdown width */
        select.dropdown-fixed {
            -moz-appearance: none;
            -webkit-appearance: none;
            appearance: none;
        }
    </style>

    <div x-data="pengirimanShowPage()" x-init="init()" class="space-y-6">

        {{-- 🔙 Tombol Kembali --}}
        <div>
            <a href="{{ route('pengiriman.index') }}"
                class="inline-flex items-center gap-2 text-sm text-slate-600 hover:text-[#334976] font-medium transition-colors">
                <i class="fa-solid fa-arrow-left text-gray-600 hover:text-[#334976]"></i>
                <span>Kembali</span>
            </a>
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
                    <input type="text" readonly
                        value="{{ $pengiriman->tanggal_pengiriman ? \Carbon\Carbon::parse($pengiriman->tanggal_pengiriman)->format('d-m-Y, H:i') : '-' }}"
                        class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-slate-50 text-slate-600" />

                </div>
            </div>

            {{-- Dropdown Status --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Status Pengiriman</label>
                <div class="relative">
                    <select x-model="form.status_pengiriman" @change="checkChanged()"
                        @cannot('pengiriman.update') disabled @endcannot disabled
                        class="appearance-none w-full px-3 pr-10 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-200 focus:border-blue-500 text-slate-700 bg-white dropdown-fixed disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                        <option value="perlu_dikirim">Perlu Dikirim</option>
                        <option value="dalam_pengiriman">Dalam Pengiriman</option>
                        <option value="diterima">Diterima</option>
                    </select>

                    {{-- Custom Icon --}}
                    <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
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
                            <th class="px-4 py-3 text-center w-40">Gudang</th>
                            <th class="px-4 py-3 text-center w-20">Jumlah</th>
                            <th class="px-4 py-3 text-center w-28">Satuan</th>
                            <th class="px-4 py-3 text-center w-32">Harga</th>
                            <th class="px-4 py-3 text-center w-40">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $subtotal = 0; @endphp
                        @foreach ($pengiriman->penjualan->items as $idx => $it)
                            @php
                                $rowTotal = $it->jumlah * $it->harga;
                                $subtotal += $rowTotal;
                            @endphp
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-100">
                                <td class="px-4 py-3 text-center">{{ $idx + 1 }}</td>
                                <td class="px-4 py-3">{{ $it->item->nama_item ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">{{ $it->gudang->nama_gudang ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">
                                    {{ fmod($it->jumlah, 1) == 0 ? number_format($it->jumlah, 0, ',', '.') : number_format($it->jumlah, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-center">{{ $it->satuan->nama_satuan ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">Rp {{ number_format($it->harga, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center font-medium">Rp
                                    {{ number_format($rowTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ringkasan & Aksi --}}
        <div class="flex flex-col md:flex-row md:justify-end gap-4 mt-8">
            <div class="w-full md:w-96 bg-gradient-to-b from-white to-slate-50 border border-slate-200 rounded-2xl p-6 ">
                {{-- Sub Total --}}
                <div class="flex justify-between items-center mb-4">
                    <div class="text-slate-600">Sub Total</div>
                    <div class="font-normal text-slate-700">
                        Rp {{ number_format($subtotal, 0, ',', '.') }}
                    </div>
                </div>

                {{-- Biaya Transportasi (tampilkan hanya jika ada) --}}
                @if ($pengiriman->penjualan->biaya_transport > 0)
                    <div class="flex justify-between items-center mb-4">
                        <div class="text-slate-600">Biaya Transportasi</div>
                        <div class="font-normal text-slate-700">
                            Rp {{ number_format($pengiriman->penjualan->biaya_transport, 0, ',', '.') }}
                        </div>
                    </div>
                @endif

                {{-- Garis pemisah --}}
                <div class="border-t border-slate-200 pt-4 mt-4"></div>

                {{-- Total --}}
                <div class="flex justify-between items-center ">
                    <div class="text-slate-700 font-bold text-lg">TOTAL</div>
                    <div class="text-[#334976] text-2xl font-extrabold tracking-wide">
                        Rp {{ number_format($subtotal + ($pengiriman->penjualan->biaya_transport ?? 0), 0, ',', '.') }}
                    </div>
                </div>

                {{-- Tombol Aksi --}}
                {{-- <div class="flex justify-end gap-3 mt-6 w-full">
                    <a href="{{ route('pengiriman.index') }}"
                        class="px-5 py-2.5 rounded-lg border border-gray-400 text-gray-600 hover:bg-gray-100 transition">
                        Kembali
                    </a>
                    @can('pengiriman.update')
                        <button @click="updateStatus" type="button"
                            :disabled="!changed || form.status_pengiriman === 'diterima'"
                            :class="[
                                'px-5 py-2.5 rounded-lg text-white font-medium w-full transition',
                                (form.status_pengiriman === 'diterima') ?
                                'bg-gray-300 cursor-not-allowed' :
                                (!changed ? 'bg-gray-300 cursor-not-allowed' : 'bg-[#334976] hover:bg-[#2d3f6d]')
                            ]">
                            Simpan Perubahan
                        </button>
                    @else
                        <span class="px-5 py-2.5 rounded-lg bg-slate-100 text-slate-500 text-sm text-center w-full">
                            <i class="fa-solid fa-eye mr-2"></i> Mode Lihat Saja
                        </span>
                    @endcan --}}


                </div>
            </div>
        </div>

        @cannot('pengiriman.update')
            <style>
                select:disabled {
                    background-color: #f8fafc !important;
                    cursor: not-allowed !important;
                    opacity: 0.7;
                }
            </style>
        @endcannot
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
                            body: JSON.stringify({
                                status_pengiriman: this.form.status_pengiriman
                            })
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
