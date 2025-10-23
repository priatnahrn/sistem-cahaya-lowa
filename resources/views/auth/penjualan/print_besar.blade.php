<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Besar</title>

    <style>
        /* ======= SETTING KERTAS CF K2 W PRS (5.5 inch per nota) ======= */
        @page {
            size: 9.5in 5.5in landscape;
            margin: 6mm 8mm;
        }

        html,
        body,
        * {
            font-family: 'Courier New', 'Consolas', 'Monaco', monospace !important;
            box-sizing: border-box;
            color: #000;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-size: 15px;
            margin: 0;
            padding: 0;
            color: #000;
            font-weight: normal;
        }

        .nota {
            width: 100%;
            min-height: 5.2in;
            max-height: 5.3in;
            overflow: hidden;
            page-break-after: always;
        }

        /* ======= HEADER ======= */
        .header-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .header-section img {
            width: 38px;
            height: 38px;
            margin-right: 8px;
            object-fit: contain;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
        }

        .barcode {
            text-align: right;
        }

        /* ======= GARIS PEMBATAS ======= */
        .line {
            border-top: 1px solid #000;
            margin: 2px 0;
        }

        /* ======= TABEL ======= */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
        }

        th,
        td {
            padding: 1px 0;
            vertical-align: top;
            color: #000;
        }

        th {
            text-align: left;
            font-weight: bold;
        }

        td.right,
        th.right {
            text-align: right;
        }

        /* ======= INFO TABLE ======= */
        .info-table {
            font-size: 15px;
            margin: 3px 0;
        }

        .info-table td {
            padding: 0;
            line-height: 1.3;
        }

        /* ======= TABEL ITEM ======= */
        .content-table {
            margin: 2px 0;
        }

        .content-table thead th {
            font-weight: bold;
            padding-bottom: 2px;
        }

        .content-table tbody td {
            font-weight: normal;
            color: #000;
            line-height: 1.3;
            border: none !important;
            letter-spacing: 0;
            padding: 1px 0;
        }

        .content-table tbody tr td:first-child {
            width: 40px;
        }

        .item-note {
            font-size: 13px;
            color: #000;
            margin-top: -1px;
            margin-left: 40px;
            font-weight: normal;
        }

        /* ======= FOOTER ======= */
        .footer-grid {
            width: 100%;
            margin-top: 6px;
            font-size: 14px;
            color: #000;
            font-weight: normal;
        }

        .footer-left {
            vertical-align: top;
            line-height: 1.3;
        }

        .footer-left div {
            margin-bottom: 1px;
        }

        .footer-right {
            text-align: right;
            vertical-align: top;
            line-height: 1.3;
        }

        .footer-right div {
            margin-bottom: 2px;
        }

        .footer-left b,
        .footer-right .bold {
            font-weight: bold;
        }

        /* ======= PRINT MODE ======= */
        @media print {
            @page {
                size: 9.5in 5.5in landscape;
                margin: 6mm 8mm;
            }

            body {
                margin: 0;
                padding: 0;
                -webkit-font-smoothing: antialiased;
                -moz-osx-font-smoothing: grayscale;
            }

            .nota {
                page-break-after: always !important;
                page-break-inside: avoid;
            }

            * {
                text-shadow: none !important;
                filter: none !important;
            }

            .content-table tr {
                page-break-inside: avoid;
            }

            .footer-grid {
                page-break-inside: avoid;
            }

          
        }
    </style>

</head>

<body>
    <div class="nota">
        {{-- HEADER --}}
        <div class="header-section">
            <img src="{{ url('storage/app/public/images/logo-cahaya-lowa-hitam.png') }}" alt="Logo CV Cahaya Lowa">
            <div class="header-text">
                <div class="company-name">CV CAHAYA LOWA</div>
                <div style="font-size: 15px;">Anabanua, Kab. Wajo</div>
            </div>
            <div class="header-right">
                <div class="barcode">
                    {!! $barcode !!}
                </div>
            </div>
        </div>

        <div class="line"></div>

        {{-- INFO TABLE --}}
        <table class="info-table">
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
                <td>: {{ $penjualan->pelanggan->kontak ?? '-' }}</td>
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
        <table class="content-table">
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
                        <td>{{ number_format($it->jumlah, fmod($it->jumlah, 1) ? 2 : 0, ',', '.') }}
                            {{ $it->satuan->nama_satuan ?? 'PCS' }}</td>
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
                    <div><b>HARGA SUDAH TERMASUK PPN</b></div>
                </td>
                <td class="footer-right">
                    @php
                        $grandTotal = $penjualan->total ?? $total + ($penjualan->biaya_transport ?? 0);
                        $totalBayar = $penjualan->pembayarans->where('jumlah_bayar', '>', 0)->sum('jumlah_bayar') ?? 0;
                        $sisaTagihan = $grandTotal - $totalBayar;
                    @endphp

                    <div>Subtotal : Rp {{ number_format($penjualan->sub_total ?? $total, 0, ',', '.') }}</div>
                    <div>Biaya Kirim : Rp {{ number_format($penjualan->biaya_transport ?? 0, 0, ',', '.') }}</div>
                    <div class="bold" style="font-size:15px;">TOTAL : Rp
                        {{ number_format($grandTotal, 0, ',', '.') }}</div>

                    @if ($totalBayar > 0 && $sisaTagihan > 0)
                        <div style="margin-top: 3px;">Jumlah Bayar : Rp {{ number_format($totalBayar, 0, ',', '.') }}
                        </div>
                        <div class="bold" style="color: #cc0000;">SISA : Rp
                            {{ number_format($sisaTagihan, 0, ',', '.') }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- ✅ FORM FEED untuk auto stop --}}
    <div style="page-break-after: always; height: 0;"></div>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = () => window.close();
            setTimeout(() => {
                if (!window.closed) window.close();
            }, 2000);
        };
    </script>
</body>

</html>