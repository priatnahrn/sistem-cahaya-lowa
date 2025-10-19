<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;

class GudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'kode_gudang' => 'GDG-001',
                'nama_gudang' => 'Gudang 1',
                'lokasi'      => 'Jakarta',
            ],
            [
                'kode_gudang' => 'GDG-002',
                'nama_gudang' => 'Gudang 2',
                'lokasi'      => 'Bandung',
            ],
            [
                'kode_gudang' => 'GDG-003',
                'nama_gudang' => 'Gudang 3',
                'lokasi'      => 'Surabaya',
            ],
            [
                'kode_gudang' => 'GDG-004',
                'nama_gudang' => 'Gudang 4',
                'lokasi'      => 'Medan',
            ],
        ];

        foreach ($data as $gudang) {
            Gudang::create(array_merge($gudang, [
                'created_by' => 1,   // ID user admin (ganti kalau perlu)
                'updated_by' => 1,   // optional, kalau kolomnya ada
            ]));
        }
    }
}
