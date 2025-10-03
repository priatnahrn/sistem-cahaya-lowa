<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Nota Kecil</title>
    <style>
        @page {
            size: 58mm auto;
            /* lebar fix 80mm */
            margin: 0;
        }

        body {
            font-family: monospace;
            font-size: 12px;
            margin: 0;
            padding: 5px;
            width: 80mm;
        }

        .center {
            text-align: center;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .flex {
            display: flex;
            justify-content: space-between;
        }

        .right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body onload="window.print()">

    <div class="center">
        <strong>CV CAHAYA LOWA</strong><br>
        Anabanua, Kab. Wajo<br>
        No. Telp: 082391497127
    </div>

    <div class="line"></div>
    No Faktur : {{ $penjualan->no_faktur }}<br>
    Tanggal : {{ \Carbon\Carbon::parse($penjualan->tanggal)->format('d/m/Y H:i') }}<br>
    Admin : {{ $penjualan->createdBy->name ?? '-' }}
    <div class="line"></div>

    {{-- List Items --}}
    @foreach ($penjualan->items as $it)
        <div>{{ $it->item->nama_item }}</div>
        <div class="flex">
            <div>{{ $it->jumlah }} {{ $it->satuan->nama_satuan ?? 'PCS' }} x Rp
                {{ number_format($it->harga, 0, ',', '.') }}</div>
            <div>Rp {{ number_format($it->total, 0, ',', '.') }}</div>
        </div>
    @endforeach

    <div class="line"></div>
    <div class="flex bold">
        <div>TOTAL</div>
        <div>Rp {{ number_format($penjualan->total, 0, ',', '.') }}</div>
    </div>
    <div class="line"></div>

    <div class="center">
        Terima Kasih atas kunjungan anda.<br>
        Barang yang sudah dibeli tidak dapat ditukar.
    </div>

</body>

</html>
