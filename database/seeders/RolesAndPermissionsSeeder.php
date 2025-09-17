<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // daftar permissions dari kebutuhan sistem
        $permissions = [
            'auth.login',

            // Item
            'item.create','item.update','item.delete','item.view','item.export','item.search','item.filter','item.show',
            'category.create','category.update','category.delete','category.view',

            // Gudang
            'gudang.create','gudang.update','gudang.delete','gudang.view','gudang.show',

            // User
            'user.create','user.update','user.delete','user.view',

            // Penjualan
            'penjualan.create','penjualan.pending','penjualan.update','penjualan.delete',
            'penjualan.addRow','penjualan.removeRow','penjualan.print','penjualan.view','penjualan.show',

            // Pembelian
            'pembelian.create','pembelian.pending','pembelian.update','pembelian.delete',
            'pembelian.addRow','pembelian.removeRow',

            // Supplier
            'supplier.create','supplier.update','supplier.delete','supplier.view',

            // Pelanggan
            'pelanggan.create','pelanggan.update','pelanggan.delete','pelanggan.view',

            // Kasir
            'kasir.create','kasir.pending','kasir.update','kasir.delete','kasir.print','kasir.priceList',

            // Kas Keuangan
            'cashIn.create','cashOut.create','cashflow.view','cashflow.export',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // roles
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin      = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $user       = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $guest      = Role::firstOrCreate(['name' => 'guest', 'guard_name' => 'web']);

        // role permission policy (contoh, bisa diubah sesuai kebutuhan)
        $superAdmin->syncPermissions(Permission::all()); // full akses

       
    }
}
