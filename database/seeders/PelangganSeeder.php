<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'nama_pelanggan' => 'Budi Santoso',
                'kontak'         => '081234567890',
                'alamat'         => 'Jl. Merdeka No. 10, Jakarta',
                'level'          => 'Retail',
            ],
            [
                'nama_pelanggan' => 'Toko Sumber Makmur',
                'kontak'         => '082233445566',
                'alamat'         => 'Jl. Braga No. 25, Bandung',
                'level'          => 'Partai Kecil',
            ],
            [
                'nama_pelanggan' => 'CV Maju Jaya',
                'kontak'         => '085677889900',
                'alamat'         => 'Jl. Diponegoro No. 7, Surabaya',
                'level'          => 'Grosir',
            ],
            [
                'nama_pelanggan' => 'Siti Aminah',
                'kontak'         => null,
                'alamat'         => 'Jl. Sudirman No. 88, Medan',
                'level'          => 'Retail',
            ],
        ];

        foreach ($data as $pelanggan) {
            Pelanggan::create($pelanggan);
        }
    }
}
