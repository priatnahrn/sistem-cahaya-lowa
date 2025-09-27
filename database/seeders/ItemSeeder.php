<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Satuan;
use App\Models\KategoriItem;
use App\Models\Gudang;
use App\Models\ItemGudang;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // pastikan kategori & gudang ada
            $kategoriSemen = KategoriItem::firstOrCreate(['nama_kategori' => 'Semen']);
            $kategoriCat   = KategoriItem::firstOrCreate(['nama_kategori' => 'Cat & Pelapis']);
            $kategoriBesi  = KategoriItem::firstOrCreate(['nama_kategori' => 'Besi & Baja']);

            // kalau belum ada gudang sama sekali, bikin default
            if (Gudang::count() === 0) {
                Gudang::create([
                    'kode_gudang' => 'GDG-001',
                    'nama_gudang' => 'Gudang Default',
                    'lokasi'      => 'Jakarta',
                ]);
            }

            $items = [
                [
                    'kode_item' => 'SMN-001',
                    'nama_item' => 'Semen Tiga Roda 40Kg',
                    'kategori_item_id' => $kategoriSemen->id,

                    'satuans' => [
                        [
                            'nama_satuan' => 'Sak',
                            'jumlah' => 1,
                            'is_base' => true,
                            'harga_retail' => 60000,
                            'partai_kecil' => 59000,
                            'harga_grosir' => 58000,
                        ],
                        [
                            'nama_satuan' => 'Pallet',
                            'jumlah' => 40,
                            'is_base' => false,
                            'harga_retail' => 2400000,
                            'partai_kecil' => 2350000,
                            'harga_grosir' => 2300000,
                        ],
                    ],
                    'satuan_primary_index' => 0,
                ],
                [
                    'kode_item' => 'CAT-005',
                    'nama_item' => 'Cat Tembok 5L',
                    'kategori_item_id' => $kategoriCat->id,

                    'satuans' => [
                        [
                            'nama_satuan' => 'Kaleng 5L',
                            'jumlah' => 1,
                            'is_base' => true,
                            'harga_retail' => 250000,
                            'partai_kecil' => 245000,
                            'harga_grosir' => 240000,
                        ],
                        [
                            'nama_satuan' => 'Dus (4 Kaleng)',
                            'jumlah' => 4,
                            'is_base' => false,
                            'harga_retail' => 960000,
                            'partai_kecil' => 940000,
                            'harga_grosir' => 930000,
                        ],
                    ],
                    'satuan_primary_index' => 0,
                ],
                [
                    'kode_item' => 'BES-010',
                    'nama_item' => 'Besi Beton Ø10mm (12m)',
                    'kategori_item_id' => $kategoriBesi->id,
                    'satuans' => [
                        [
                            'nama_satuan' => 'Batang',
                            'jumlah' => 1,
                            'is_base' => true,
                            'harga_retail' => 75000,
                            'partai_kecil' => 74000,
                            'harga_grosir' => 73000,
                        ],
                        [
                            'nama_satuan' => 'Ikat (10 Batang)',
                            'jumlah' => 10,
                            'is_base' => false,
                            'harga_retail' => 740000,
                            'partai_kecil' => 730000,
                            'harga_grosir' => 720000,
                        ],
                    ],
                    'satuan_primary_index' => 0,
                ],
            ];

            foreach ($items as $data) {
                // buat item
                $item = Item::create([
                    'kode_item' => $data['kode_item'],
                    'nama_item' => $data['nama_item'],
                    'kategori_item_id' => $data['kategori_item_id'],
                    'foto_path' => null,
                ]);

                // buat satuan
                $satuanIds = [];
                foreach ($data['satuans'] as $idx => $s) {
                    $created = Satuan::create([
                        'item_id' => $item->id,
                        'nama_satuan' => $s['nama_satuan'],
                        'jumlah' => $s['jumlah'] ?? 1,
                        'is_base' => $s['is_base'] ?? false,
                        'harga_retail' => $s['harga_retail'] ?? null,
                        'partai_kecil' => $s['partai_kecil'] ?? null,
                        'harga_grosir' => $s['harga_grosir'] ?? null,
                    ]);
                    $satuanIds[$idx] = $created->id;
                }

                // set primary satuan
                if (isset($satuanIds[$data['satuan_primary_index']])) {
                    $item->primary_satuan_id = $satuanIds[$data['satuan_primary_index']];
                    $item->save();
                } elseif (!empty($satuanIds)) {
                    $item->primary_satuan_id = reset($satuanIds);
                    $item->save();
                }

                // masukkan ke semua gudang dengan stok awal 0
                // masukkan ke semua gudang dengan stok awal 0
                $gudangs = Gudang::all();
                $itemGudangData = $gudangs->map(fn($g) => [
                    'item_id'   => $item->id,
                    'gudang_id' => $g->id,
                    'satuan_id' => $item->primary_satuan_id, // tambahin ini!
                    'stok'      => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->toArray();

                ItemGudang::insert($itemGudangData);
            }
        });
    }


    public function findByBarcode($kode)
    {
        $item = Item::with('satuans')
            ->where('barcode', $kode) // kalau ada kolom barcode
            ->orWhere('kode_item', $kode) // fallback ke kode_item
            ->first();

        if (!$item) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($item);
    }
}
