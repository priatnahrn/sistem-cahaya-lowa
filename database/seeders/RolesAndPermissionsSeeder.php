<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        echo "🔄 Memulai seeding permissions dan roles...\n\n";

        // ================================
        // 📋 DAFTAR SEMUA MODULE
        // ================================
        $modules = [
            'dashboard',
            'penjualan',
            'penjualan_cepat',
            'retur_penjualan',
            'tagihan_penjualan',
            'pengiriman',
            'pembayaran',
            'pembelian',
            'tagihan_pembelian',
            'retur_pembelian',
            'gudang',
            'supplier',
            'pelanggan',
            'items',
            'kategori_items',
            'produksi',
            'mutasi_stok',
            'users',
            'roles',
            'activity_logs',
            'payrolls',
            'cashflows',
            'profile',
            'cek_harga',
        ];

        // ================================
        // 🎯 ACTIONS UNTUK SETIAP MODULE
        // ================================
        $actions = ['view', 'create', 'update', 'delete'];

        // ================================
        // 🔄 GENERATE PERMISSIONS
        // ================================
        DB::beginTransaction();
        
        try {
            $totalPermissions = 0;
            
            foreach ($modules as $module) {
                echo "📦 Membuat permissions untuk module: {$module}\n";
                
                foreach ($actions as $action) {
                    $permissionName = "{$module}.{$action}";
                    
                    Permission::firstOrCreate(
                        ['name' => $permissionName],
                        ['guard_name' => 'web']
                    );
                    
                    $totalPermissions++;
                    echo "   ✅ {$permissionName}\n";
                }
            }

            echo "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "✅ Total {$totalPermissions} permissions berhasil dibuat!\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            // ================================
            // 👑 BUAT ROLE SUPER ADMIN
            // ================================
            echo "👑 Membuat role Super Admin...\n";
            
            $superAdmin = Role::firstOrCreate(
                ['name' => 'super-admin'],
                ['guard_name' => 'web']
            );

            // Assign SEMUA permissions ke Super Admin
            $allPermissions = Permission::all();
            $superAdmin->syncPermissions($allPermissions);

            echo "✅ Role 'super-admin' berhasil dibuat!\n";
            echo "✅ Super Admin memiliki {$allPermissions->count()} permissions\n\n";

            DB::commit();

            // ================================
            // 📊 SUMMARY
            // ================================
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "📊 SUMMARY\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "✅ Total Permissions: " . Permission::count() . "\n";
            echo "✅ Total Roles: " . Role::count() . "\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

            // ================================
            // 🎉 SUCCESS MESSAGE
            // ================================
            echo "🎉 Seeding berhasil!\n";
            echo "💡 Jangan lupa assign role 'super-admin' ke user Anda\n";
            echo "💡 Gunakan: php artisan tinker\n";
            echo "💡 Lalu: \$user = User::find(1); \$user->assignRole('super-admin');\n\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Error: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}