<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Besar</title>
    <style>
        @page {
            size: 9.5in 11in landscape;
            margin: 8mm;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .company-name {
            font-weight: bold;
            font-size: 12px;
        }

        .line {
            border-top: 1px solid #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th,
        td {
            padding: 2px 3px;
        }

        th {
            border-bottom: 1px solid #000;
            text-align: left;
            font-weight: normal;
        }

        td.right,
        th.right {
            text-align: right;
        }

        .footer-grid {
            width: 100%;
            margin-top: 10px;
        }

        .footer-left {
            font-size: 9px;
            vertical-align: top;
        }

        .footer-right {
            text-align: right;
            font-size: 10px;
            vertical-align: top;
        }

        .footer-right div {
            margin-bottom: 2px;
        }

        .footer-right .bold {
            font-weight: bold;
        }

        .item-note {
            font-size: 9px;
            color: #333;
            margin-top: -2px;
            margin-left: 35px;
        }

        .header-section {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .header-section img {
            width: 30px;
            height: 30px;
            margin-right: 10px;
            object-fit: contain;
        }

        .header-text {
            flex: 1;
        }
    </style>
</head>

<body>
    {{-- HEADER --}}
    <div class="header-section">
        <img src="{{ url('storage/app/public/images/logo-cahaya-lowa-hitam.png') }}" alt="Logo CV Cahaya Lowa">
        <div class="header-text">
            <div class="company-name">CV CAHAYA LOWA</div>
            <div>Anabanua, Kab. Wajo</div>
        </div>
    </div>

    {{-- INFO TABLE --}}
    <table style="width:100%; font-size:10px; margin-top:5px;">
        <tr>
            <td style="width:80px;">NPWP</td>
            <td style="width:150px;">: {{ $penjualan->pelanggan->npwp ?? '0' }}</td>
            <td style="width:80px;">Nota #</td>
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
                <th style="width:30px;">NO</th>
                <th>NAMA BARANG</th>
                <th style="width:90px;">BANYAK</th>
                <th class="right" style="width:90px;">HARGA</th>
                <th class="right" style="width:100px;">SUBTOTAL</th>
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
                    <td>{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
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

    {{-- FOOTER --}}
    <table class="footer-grid">
        <tr>
            <td class="footer-left" style="width:60%;">
                <div><b>PERHATIAN :</b></div>
                <div>1. Barang yang sudah dibeli tidak dapat dikembalikan/ditukar.</div>
                <div>2. Pembayaran dengan Cek/BG dianggap lunas setelah dicairkan.</div>
                <div>3. Periksa kembali barang sebelum meninggalkan toko.</div>
            </td>
            <td class="footer-right">
                @php
                    $grandTotal = $penjualan->total ?? $total + ($penjualan->biaya_transport ?? 0);

                    // Hitung total pembayaran langsung dari tabel pembayarans
                    $totalBayar = 0;
                    if (isset($penjualan->pembayarans)) {
                        $totalBayar = $penjualan->pembayarans->where('jumlah_bayar', '>', 0)->sum('jumlah_bayar');
                    }

                    $sisaTagihan = $grandTotal - $totalBayar;
                @endphp

                <div>Subtotal : Rp {{ number_format($penjualan->sub_total ?? $total, 0, ',', '.') }}</div>
                <div>Biaya Kirim : Rp {{ number_format($penjualan->biaya_transport ?? 0, 0, ',', '.') }}</div>
                <div class="bold">TOTAL : Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>

                @if ($totalBayar > 0 && $sisaTagihan > 0)
                    <div style="margin-top: 3px;">Jumlah Bayar : Rp {{ number_format($totalBayar, 0, ',', '.') }}</div>
                    <div class="bold" style="color: #cc0000;">SISA : Rp
                        {{ number_format($sisaTagihan, 0, ',', '.') }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>

</html>
