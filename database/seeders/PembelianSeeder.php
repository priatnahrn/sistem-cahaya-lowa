<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        $supplierId = DB::table('suppliers')->value('id');
        $gudangId   = DB::table('gudangs')->value('id');
        $itemId     = DB::table('items')->value('id');
        $satuan     = DB::table('satuans')->where('item_id', $itemId)->first();

        if (!$supplierId || !$gudangId || !$itemId || !$satuan) {
            $this->command->warn("Seeder gagal: supplier, item, gudang, atau satuan belum ada di DB.");
            return;
        }

        for ($i = 1; $i <= 5; $i++) {
            $jumlah         = 10 * $i;
            $harga          = 10000 * $i;
            $subTotal       = $jumlah * $harga;
            $biayaTransport = 5000;

            // insert pembelian
            $pembelianId = DB::table('pembelians')->insertGetId([
                'supplier_id'     => $supplierId,
                'no_faktur'       => 'PB-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'tanggal'         => Carbon::now()->subDays($i)->toDateTimeString(),
                'deskripsi'       => 'Pembelian dummy ke-' . $i,
                'sub_total'       => $subTotal,
                'biaya_transport' => $biayaTransport,
                'total'           => $subTotal + $biayaTransport,
                'status'          => $i % 2 == 0 ? 'paid' : 'unpaid',
                'created_at'      => now(),
                'updated_at'      => now(),
                'created_by'      => 1,
                'updated_by'      => 1,
            ]);

            // insert detail item pembelian
            DB::table('item_pembelians')->insert([
                'pembelian_id'     => $pembelianId,
                'item_id'          => $itemId,
                'gudang_id'        => $gudangId,
                'satuan_id'        => $satuan->id,
                'jumlah'           => $jumlah,
                'harga_sebelumnya' => 0,
                'harga_beli'       => $harga,
                'total'            => $subTotal,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // ===== Update stok di item_gudang =====
            $itemGudang = DB::table('item_gudangs')
                ->where('item_id', $itemId)
                ->where('gudang_id', $gudangId)
                ->where('satuan_id', $satuan->id)
                ->first();

            if ($itemGudang) {
                // update stok lama
                DB::table('item_gudangs')
                    ->where('id', $itemGudang->id)
                    ->update([
                        'stok'       => $itemGudang->stok + $jumlah,
                        'updated_at' => now(),
                    ]);
            } else {
                // buat record baru
                DB::table('item_gudangs')->insert([
                    'item_id'    => $itemId,
                    'gudang_id'  => $gudangId,
                    'satuan_id'  => $satuan->id,
                    'stok'       => $jumlah,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ===== Hitung ulang total_stok (base unit) =====
            $stokGudang = DB::table('item_gudangs')
                ->join('satuans', 'item_gudangs.satuan_id', '=', 'satuans.id')
                ->where('item_gudangs.item_id', $itemId)
                ->where('item_gudangs.gudang_id', $gudangId)
                ->select('item_gudangs.id', 'item_gudangs.stok', 'satuans.jumlah')
                ->get();

            $totalStok = $stokGudang->sum(fn($row) => $row->stok * $row->jumlah);

            // update semua baris untuk item+gudang dengan total_stok
            DB::table('item_gudangs')
                ->where('item_id', $itemId)
                ->where('gudang_id', $gudangId)
                ->update(['total_stok' => $totalStok, 'updated_at' => now()]);
        }
    }
}
