<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Besar</title>
    <style>
        @page {
            size: 9.5in 11in landscape;
            margin: 4mm;
        }

        /* Gunakan Calibri di seluruh elemen */
        * {
            font-family: Calibri, Arial, sans-serif;
            box-sizing: border-box;
        }

        body {
            font-size: 14px;
            margin: 0;
            padding: 0;
            color: #000;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .nota {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 5in; /* setengah dari 11 inch */
            padding-bottom: 6px;
        }

        .company-name {
            font-weight: bold;
            font-size: 20px;
        }

        .line {
            border-top: 1px solid #000;
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th,
        td {
            padding: 0px;
            vertical-align: top;
        }

        th {
            border-bottom: 1px solid #000;
            text-align: left;
            font-weight: 500;
        }

        td.right,
        th.right {
            text-align: right;
        }

        .footer-grid {
            width: 100%;
            margin-top: 12px;
        }

        .footer-left {
            font-size: 12px;
            vertical-align: top;
        }

        .footer-right {
            text-align: right;
            font-size: 14px;
            vertical-align: top;
        }

        .footer-right div {
            margin-bottom: 3px;
        }

        .footer-right .bold {
            font-weight: bold;
        }

        .item-note {
            font-size: 13px;
            color: #333;
            margin-top: -2px;
            margin-left: 40px;
        }

        .header-section {
            display: flex;
            align-items: center;
            margin-bottom: 6px;
        }

        .header-section img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
        }

        .page-divider {
            border-top: 1px dashed #888;
            margin: 2px 0;
        }
    </style>
</head>

<body>
    <div class="nota">
        {{-- HEADER --}}
        <div>
            <div class="header-section">
                <img src="{{ url('storage/app/public/images/logo-cahaya-lowa-hitam.png') }}" alt="Logo CV Cahaya Lowa">
                <div class="header-text">
                    <div class="company-name">CV CAHAYA LOWA</div>
                    <div style="font-size: 13px;">Anabanua, Kab. Wajo</div>
                </div>
            </div>

            {{-- INFO TABLE --}}
            <table style="width:100%; font-size:14px; margin-top:6px;">
                <tr>
                    <td style="width:90px;">NPWP</td>
                    <td style="width:180px;">: {{ $penjualan->pelanggan->npwp ?? '0' }}</td>
                    <td style="width:90px;">Nota #</td>
                    <td>: {{ $penjualan->no_faktur }}</td>
                </tr>
                <tr>
                    <td>Telp</td>
                    <td>: 0811 4284 995</td>
                    <td>Pelanggan</td>
                    <td>: {{ $penjualan->pelanggan->nama_pelanggan ?? 'CUSTOMER' }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d/m/Y') }}</td>
                    <td>Telepon</td>
                    <td>: {{ $penjualan->pelanggan->telepon ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Admin</td>
                    <td>: {{ $penjualan->createdBy->name ?? '-' }}</td>
                    <td>Alamat</td>
                    <td>: {{ $penjualan->pelanggan->alamat ?? '-' }}</td>
                </tr>
            </table>

            <div class="line"></div>

            {{-- TABEL ITEM --}}
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">GD</th>
                        <th>NAMA BARANG</th>
                        <th style="width:100px;">BANYAK</th>
                        <th class="right" style="width:110px;">HARGA</th>
                        <th class="right" style="width:120px;">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php $total = 0; @endphp
                    @foreach ($penjualan->items as $i => $it)
                        @php
                            $subtotal = $it->jumlah * $it->harga;
                            $total += $subtotal;
                        @endphp
                        <tr>
                            <td>{{ $it->gudang->kode_gudang ?? '-' }}</td>
                            <td>
                                {{ strtoupper($it->item->nama_item ?? '-') }}
                                @if (!empty($it->keterangan))
                                    <div class="item-note">- {{ $it->keterangan }}</div>
                                @endif
                            </td>
                            <td>
                                {{ number_format($it->jumlah, fmod($it->jumlah, 1) ? 2 : 0, ',', '.') }}
                                {{ $it->satuan->nama_satuan ?? 'PCS' }}
                            </td>
                            <td class="right">{{ number_format($it->harga, 0, ',', '.') }}</td>
                            <td class="right">{{ number_format($subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="line"></div>
        </div>

        {{-- FOOTER --}}
        <table class="footer-grid">
            <tr>
                <td class="footer-left" style="width:60%;">
                    <div><b>PERHATIAN :</b></div>
                    <div>1. Barang yang sudah dibeli tidak dapat dikembalikan/ditukar.</div>
                    <div>2. Pembayaran dengan Cek/BG dianggap lunas setelah dicairkan.</div>
                    <div>HARGA SUDAH TERMASUK PPN</div>
                </td>
                <td class="footer-right">
                    @php
                        $grandTotal = $penjualan->total ?? $total + ($penjualan->biaya_transport ?? 0);
                        $totalBayar = $penjualan->pembayarans->where('jumlah_bayar', '>', 0)->sum('jumlah_bayar') ?? 0;
                        $sisaTagihan = $grandTotal - $totalBayar;
                    @endphp

                    <div>Subtotal : Rp {{ number_format($penjualan->sub_total ?? $total, 0, ',', '.') }}</div>
                    <div>Biaya Kirim : Rp {{ number_format($penjualan->biaya_transport ?? 0, 0, ',', '.') }}</div>
                    <div class="bold" style="font-size:15px;">TOTAL : Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>

                    @if ($totalBayar > 0 && $sisaTagihan > 0)
                        <div style="margin-top: 4px;">Jumlah Bayar : Rp {{ number_format($totalBayar, 0, ',', '.') }}</div>
                        <div class="bold" style="color: #cc0000;">SISA : Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="page-divider"></div>

    {{-- Jika ingin dua nota dalam satu halaman, copy ulang blok .nota di sini --}}
</body>

</html>
