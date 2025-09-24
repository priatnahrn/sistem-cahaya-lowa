<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KategoriItem;

class KategoriItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_kategori' => 'Semen'],
            ['nama_kategori' => 'Batu Bata'],
            ['nama_kategori' => 'Besi & Baja'],
            ['nama_kategori' => 'Kayu & Triplek'],
            ['nama_kategori' => 'Cat & Pelapis'],
            ['nama_kategori' => 'Pipa & Fitting'],
            ['nama_kategori' => 'Keramik & Granit'],
            ['nama_kategori' => 'Peralatan Tukang'],
            ['nama_kategori' => 'Listrik & Lampu'],
            ['nama_kategori' => 'Sanitasi & Kamar Mandi'],
            ['nama_kategori' => 'Atap & Genteng'],
            ['nama_kategori' => 'Paku, Baut & Aksesoris'],
        ];

        foreach ($data as $kategori) {
            KategoriItem::create($kategori);
        }
    }
}
