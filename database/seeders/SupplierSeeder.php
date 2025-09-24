<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'nama_supplier'   => 'PT Sumber Makmur',
                'kontak'          => '081234567890',
                'alamat'          => 'Jl. Merdeka No. 12, Jakarta',
                'nama_bank'       => 'BCA',
                'nomor_rekening'  => '1234567890',
            ],
            [
                'nama_supplier'   => 'CV Maju Jaya',
                'kontak'          => '082233445566',
                'alamat'          => 'Jl. Asia Afrika No. 22, Bandung',
                'nama_bank'       => 'Mandiri',
                'nomor_rekening'  => '9876543210',
            ],
            [
                'nama_supplier'   => 'Toko Berkah Abadi',
                'kontak'          => null,
                'alamat'          => 'Jl. Diponegoro No. 5, Surabaya',
                'nama_bank'       => 'BNI',
                'nomor_rekening'  => '5566778899',
            ],
            [
                'nama_supplier'   => 'UD Jaya Sentosa',
                'kontak'          => '081355557777',
                'alamat'          => 'Jl. Sisingamangaraja No. 9, Medan',
                'nama_bank'       => 'Lainnya',
                'nomor_rekening'  => null,
            ],
        ];

        foreach ($data as $supplier) {
            Supplier::create($supplier);
        }
    }
}
