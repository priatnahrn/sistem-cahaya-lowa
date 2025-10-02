@extends('layouts.app')

@section('title', 'Pengiriman')

@section('content')
    <style>
        [x-cloak] { display: none !important; }
    </style>

    <div x-data="pengirimanPage()" x-init="init()" class="space-y-6">
        {{-- TABLE --}}
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr class="text-left text-slate-600">
                            <th class="px-4 py-3 w-[60px]">No.</th>
                            <th class="px-4 py-3">No Faktur</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Pelanggan</th>
                            <th class="px-4 py-3">Status Pengiriman</th>
                            <th class="px-4 py-3">Created By</th>
                            <th class="px-4 py-3">Updated By</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(r, idx) in pagedData()" :key="r.id">
                            <tr class="hover:bg-slate-50 text-slate-700 border-b border-slate-200">
                                <td class="px-4 py-3" x-text="(currentPage-1)*pageSize + idx + 1"></td>
                                <td class="px-4 py-3" x-text="r.no_faktur"></td>
                                <td class="px-4 py-3" x-text="fmtTanggal(r.tanggal)"></td>
                                <td class="px-4 py-3 text-green-600" x-text="r.pelanggan"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-2 text-xs font-medium px-2.5 py-1 rounded-full"
                                        :class="badgeKirim(r.status)">
                                        <span class="w-2 h-2 rounded-full" :class="dotKirim(r.status)"></span>
                                        <span x-text="r.status"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3" x-text="r.created_by"></td>
                                <td class="px-4 py-3" x-text="r.updated_by"></td>
                                <td class="px-2 py-3 text-right relative">
                                    <a :href="r.url" class="px-3 py-1 rounded hover:bg-slate-100 text-blue-600">
                                        <i class="fa-solid fa-eye mr-1"></i> Detail
                                    </a>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredTotal()===0" class="text-center text-slate-500">
                            <td colspan="8" class="px-4 py-6">Tidak ada data pengiriman.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @php
        $pengirimanJson = $pengirimans->map(function ($p) {
            // Map status pengiriman
            $statusMap = [
                'pending' => 'Perlu Dikirim',
                'on_process' => 'Dalam Pengiriman',
                'delivered' => 'Diterima',
                'cancelled' => 'Dibatalkan',
            ];

            // Format tanggal
            $tanggal = $p->penjualan?->tanggal instanceof \Carbon\Carbon
                ? $p->penjualan->tanggal->timezone('Asia/Makassar')->format('Y-m-d H:i:s')
                : ($p->penjualan?->tanggal ?? null);

            return [
                'id'         => $p->id,
                'no_faktur'  => $p->penjualan?->no_faktur ?? '-',
                'tanggal'    => $tanggal,
                'pelanggan'  => $p->penjualan?->pelanggan?->nama_pelanggan ?? '-',
                'status'     => $statusMap[$p->status] ?? '-',
                'created_by' => $p->creator?->name ?? '-',
                'updated_by' => $p->updater?->name ?? '-',
                'url'        => route('pengiriman.show', $p->id),
            ];
        })->toArray();
    @endphp

    <script>
        function pengirimanPage() {
            return {
                pageSize: 10,
                currentPage: 1,
                data: @json($pengirimanJson),

                fmtTanggal(iso) {
                    if (!iso) return '-';
                    const d = new Date(iso);
                    return isNaN(d) ? iso : 
                        `${String(d.getDate()).padStart(2, '0')}-${String(d.getMonth() + 1).padStart(2, '0')}-${d.getFullYear()}, ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
                },

                filteredList() {
                    return this.data;
                },
                filteredTotal() {
                    return this.filteredList().length;
                },
                totalPages() {
                    return Math.max(1, Math.ceil(this.filteredTotal() / this.pageSize));
                },
                pagedData() {
                    const start = (this.currentPage - 1) * this.pageSize;
                    return this.filteredList().slice(start, start + this.pageSize);
                },

                badgeKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-50 text-orange-700 border border-orange-200';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-50 text-blue-700 border border-blue-200';
                    if (st === 'Diterima') return 'bg-green-50 text-green-700 border border-green-200';
                    return 'bg-slate-50 text-slate-600 border border-slate-200';
                },
                dotKirim(st) {
                    if (st === 'Perlu Dikirim') return 'bg-orange-500';
                    if (st === 'Dalam Pengiriman') return 'bg-blue-500';
                    if (st === 'Diterima') return 'bg-green-500';
                    return 'bg-slate-500';
                },
            }
        }
    </script>
@endsection
