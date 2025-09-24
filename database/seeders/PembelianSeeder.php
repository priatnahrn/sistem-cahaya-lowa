<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        // ambil id supplier, item, gudang, satuan yang sudah ada
        $supplierId = DB::table('suppliers')->value('id');
        $gudangId   = DB::table('gudangs')->value('id');
        $itemId     = DB::table('items')->value('id');
        $satuanId   = DB::table('satuans')->where('item_id', $itemId)->value('id');

        if (!$supplierId || !$gudangId || !$itemId || !$satuanId) {
            $this->command->warn("Seeder gagal: supplier, item, gudang, atau satuan belum ada di DB.");
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $subTotal = 10 * (10000 * $i);
            $biayaTransport = 5000;

            // insert pembelian
            $pembelianId = DB::table('pembelians')->insertGetId([
                'supplier_id'    => $supplierId,
                'no_faktur'      => 'PB-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'tanggal'        => Carbon::now()->subDays($i)->toDateTimeString(),
                'deskripsi'      => 'Pembelian dummy ke-' . $i,
                'sub_total'      => $subTotal,
                'biaya_transport'=> $biayaTransport,
                'total'          => $subTotal + $biayaTransport,
                'status'         => $i % 2 == 0 ? 'paid' : 'unpaid',
                'created_at'     => now(),
                'updated_at'     => now(),
                'created_by'     => 1,
                'updated_by'     => 1,
            ]);

            // insert detail item pembelian
            DB::table('item_pembelians')->insert([
                'pembelian_id'     => $pembelianId,
                'item_id'          => $itemId,
                'gudang_id'        => $gudangId,
                'satuan_id'        => $satuanId,
                'jumlah'           => 10 * $i,
                'harga_sebelumnya' => 0,
                'harga_beli'       => 10000 * $i,
                'total'            => (10 * $i) * (10000 * $i),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }
    }
}
