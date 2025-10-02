<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Item;
use App\Models\Satuan;
use App\Models\KategoriItem;
use App\Models\Gudang;
use App\Models\ItemGudang;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Buat kategori default
            $kategoriSemen = KategoriItem::firstOrCreate(['nama_kategori' => 'Semen']);
            $kategoriCat   = KategoriItem::firstOrCreate(['nama_kategori' => 'Cat & Pelapis']);
            $kategoriBesi  = KategoriItem::firstOrCreate(['nama_kategori' => 'Besi & Baja']);

            // Buat gudang default jika kosong
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
                        ['nama_satuan' => 'Sak', 'jumlah' => 1, 'is_base' => true,  'harga_retail' => 60000, 'partai_kecil' => 59000, 'harga_grosir' => 58000],
                        ['nama_satuan' => 'Pallet', 'jumlah' => 40, 'is_base' => false, 'harga_retail' => 2400000, 'partai_kecil' => 2350000, 'harga_grosir' => 2300000],
                    ],
                    'satuan_primary_index' => 0,
                ],
                [
                    'kode_item' => 'CAT-005',
                    'nama_item' => 'Cat Tembok 5L',
                    'kategori_item_id' => $kategoriCat->id,
                    'satuans' => [
                        ['nama_satuan' => 'Kaleng 5L', 'jumlah' => 1, 'is_base' => true, 'harga_retail' => 250000, 'partai_kecil' => 245000, 'harga_grosir' => 240000],
                        ['nama_satuan' => 'Dus (4 Kaleng)', 'jumlah' => 4, 'is_base' => false, 'harga_retail' => 960000, 'partai_kecil' => 940000, 'harga_grosir' => 930000],
                    ],
                    'satuan_primary_index' => 0,
                ],
                [
                    'kode_item' => 'BES-010',
                    'nama_item' => 'Besi Beton Ø10mm (12m)',
                    'kategori_item_id' => $kategoriBesi->id,
                    'satuans' => [
                        ['nama_satuan' => 'Batang', 'jumlah' => 1, 'is_base' => true, 'harga_retail' => 75000, 'partai_kecil' => 74000, 'harga_grosir' => 73000],
                        ['nama_satuan' => 'Ikat (10 Batang)', 'jumlah' => 10, 'is_base' => false, 'harga_retail' => 740000, 'partai_kecil' => 730000, 'harga_grosir' => 720000],
                    ],
                    'satuan_primary_index' => 0,
                ],
            ];

            foreach ($items as $data) {
                // barcode otomatis
                $barcode = $data['kode_item'];
                $generator = new BarcodeGeneratorSVG();
                $barcodeSVG = $generator->getBarcode($barcode, $generator::TYPE_CODE_128);

                $barcodePath = 'barcodes/' . $barcode . '.svg';
                Storage::disk('public')->put($barcodePath, $barcodeSVG);

                // buat item
                $item = Item::create([
                    'kode_item'        => $data['kode_item'],
                    'barcode'          => $barcode,
                    'barcode_path'     => $barcodePath,
                    'nama_item'        => $data['nama_item'],
                    'stok_minimal'     => null,
                    'kategori_item_id' => $data['kategori_item_id'],
                    'foto_path'        => null,
                ]);

                // buat satuan
                $satuanIds = [];
                foreach ($data['satuans'] as $idx => $s) {
                    $created = Satuan::create([
                        'item_id'      => $item->id,
                        'nama_satuan'  => $s['nama_satuan'],
                        'jumlah'       => $s['jumlah'],
                        'is_base'      => $s['is_base'] ? 1 : 0,
                        'harga_retail' => $s['harga_retail'],
                        'partai_kecil' => $s['partai_kecil'],
                        'harga_grosir' => $s['harga_grosir'],
                    ]);
                    $satuanIds[$idx] = $created->id;
                }

                // set primary satuan
                if (isset($satuanIds[$data['satuan_primary_index']])) {
                    Satuan::where('id', $satuanIds[$data['satuan_primary_index']])->update(['is_base' => true]);
                } elseif (!empty($satuanIds)) {
                    Satuan::where('id', reset($satuanIds))->update(['is_base' => true]);
                }

                // masukkan stok awal ke semua gudang
                $gudangs = Gudang::all();
                $batch = [];
                foreach ($gudangs as $g) {
                    foreach ($satuanIds as $satuanId) {
                        $batch[] = [
                            'item_id'   => $item->id,
                            'gudang_id' => $g->id,
                            'satuan_id' => $satuanId,
                            'stok'      => 0,
                            'created_at'=> now(),
                            'updated_at'=> now(),
                        ];
                    }
                }
                ItemGudang::insert($batch);
            }
        });
    }
}
