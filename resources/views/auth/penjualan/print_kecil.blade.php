<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Nota Kecil</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 208px;
            /* 58mm ≈ 218px dikurangi sedikit agar aman */
            font-family: 'Calibri', sans-serif;
            font-size: 16px;
            /* besar +2 */
            line-height: 1.4;
            background: #fff;
        }

        .wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            margin: 0;
            padding: 0;
        }

        /* ===== HEADER ===== */
        header {
            text-align: center;
            margin-bottom: 6px;
        }

        header .title {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        header .sub {
            font-size: 16px;
            line-height: 1.3;
        }

        .barcode {
            text-align: center;
            margin: 6px 0 2px 0;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        /* ===== ITEM LIST ===== */
        .item {
            margin-bottom: 4px;
        }

        .item-name {
            font-size: 17px;
            font-weight: bold;
            /* 🔹 tidak bold */
            text-transform: uppercase;
            /* 🔹 kapital semua */
            word-wrap: break-word;
            white-space: normal;
        }

        .item-line {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 16px;
            font-weight: normal;
            /* 🔹 subtotal tidak bold */
        }

        .left {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .right {
            min-width: 80px;
            text-align: right;
            font-weight: normal;
            /* 🔹 subtotal tidak bold */
        }

        .total-harga {
            font-weight: bold;
        }

        /* ===== TOTAL ===== */
        .total-line {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 17px;
            font-weight: bold;
            margin-top: 4px;
        }

        footer {
            margin-top: 12px;
            text-align: center;
            font-size: 15px;
        }

        @media print {

            html,
            body {
                margin: 0 !important;
                padding: 0 !important;
                width: 208px !important;
                -webkit-print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        {{-- HEADER --}}
        <header>
            <div class="title">CV CAHAYA LOWA</div>
            <div class="sub">Anabanua, Kab. Wajo</div>
            <div class="sub">Telp: 082391497127</div>

            {{-- QR / BARCODE --}}
            <div class="barcode">
                {!! $barcode !!}
            </div>
        </header>

        <div class="line"></div>

        {{-- DETAIL INFO --}}
        <div style="text-align:left">
            No Faktur : {{ $penjualan->no_faktur }}<br>
            Tanggal : {{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d/m/Y H:i') }}<br>
            Admin : {{ $penjualan->createdBy->name ?? '-' }}
        </div>

        <div class="line"></div>

        {{-- ITEM LIST --}}
        <main>
            @foreach ($penjualan->items as $it)
                <div class="item">
                    <div class="item-name">{{ strtoupper($it->item->nama_item) }}</div>

                    {{-- 🔹 Tambahkan keterangan jika ada --}}
                    @if (!empty($it->keterangan))
                        <div style="font-size: 14px; margin-top: 2px;">
                            {{ $it->keterangan }}
                        </div>
                    @endif

                    <div class="item-line">
                        <div class="left">
                            {{-- 🔹 Format jumlah: tanpa desimal jika bilangan bulat, dua desimal jika tidak --}}
                            @php
                                $jumlahFormatted =
                                    fmod($it->jumlah, 1) == 0
                                        ? number_format($it->jumlah, 0, ',', '.')
                                        : number_format($it->jumlah, 2, ',', '.');
                            @endphp

                            {{ $jumlahFormatted }} {{ $it->satuan->nama_satuan ?? 'PCS' }} x Rp
                            {{ number_format($it->harga, 0, ',', '.') }}
                        </div>
                        <div class="right">
                            Rp {{ number_format($it->total, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
            @endforeach



            <div class="line" style="margin: 4px 0;"></div>

            {{-- 🔹 TOTAL --}}
            <div class="total-line">
                <div class="left">TOTAL</div>
                <div class="right total-harga">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</div>
            </div>

            <div class="line"></div>


        </main>

        {{-- FOOTER --}}
        <footer>
            Terima Kasih atas kunjungan anda.<br>
            Barang yang sudah dibeli tidak dapat ditukar.
        </footer>
    </div>

    <script>
        window.onload = function() {
            window.print();
            if (window.opener) setTimeout(() => window.close(), 1000);
        };
    </script>
</body>

</html>
