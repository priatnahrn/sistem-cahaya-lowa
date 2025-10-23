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

        /* ===== PAYMENT INFO ===== */
        .payment-line {
            display: flex;
            justify-content: space-between;
            width: 100%;
            font-size: 16px;
            margin-top: 3px;
        }

        .payment-line.sisa {
            font-weight: bold;
            color: #cc0000;
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

        img {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        {{-- HEADER --}}
        <header>
            <img src="{{ url('storage/app/public/images/logo-cahaya-lowa-hitam.png') }}" alt="Logo CV Cahaya Lowa">
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
            Tanggal :
            {{ \Carbon\Carbon::parse($penjualan->tanggal)->setTimezone('Asia/Makassar')->format('d/m/Y H:i') }}<br>
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

            {{-- 🔹 SUBTOTAL --}}
            <div class="payment-line">
                <div class="left">Subtotal</div>
                <div class="right">Rp {{ number_format($penjualan->sub_total ?? 0, 0, ',', '.') }}</div>
            </div>

            {{-- 🔹 BIAYA KIRIM (jika ada) --}}
            @if ($penjualan->biaya_transport > 0)
                <div class="payment-line">
                    <div class="left">Biaya Kirim</div>
                    <div class="right">Rp {{ number_format($penjualan->biaya_transport, 0, ',', '.') }}</div>
                </div>
            @endif

            {{-- 🔹 TOTAL --}}
            <div class="total-line">
                <div class="left">TOTAL</div>
                <div class="right total-harga">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</div>
            </div>

            @php
                // Hitung total pembayaran dari relasi pembayarans
                $totalBayar = 0;
                if (isset($penjualan->pembayarans)) {
                    $totalBayar = $penjualan->pembayarans->where('jumlah_bayar', '>', 0)->sum('jumlah_bayar');
                }

                $sisaTagihan = $penjualan->total - $totalBayar;
            @endphp

            {{-- 🔹 JUMLAH BAYAR & SISA (jika ada pembayaran) --}}
            @if ($totalBayar > 0)

                @if ($sisaTagihan > 0)
                    <div class="line" style="margin: 4px 0;"></div>

                    <div class="payment-line">
                        <div class="left">Jumlah Bayar</div>
                        <div class="right">Rp {{ number_format($totalBayar, 0, ',', '.') }}</div>
                    </div>
                    <div class="payment-line sisa">
                        <div class="left">SISA</div>
                        <div class="right">Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</div>
                    </div>
                @endif
            @endif

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
