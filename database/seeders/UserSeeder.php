<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        $user = User::firstOrCreate([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'phone' => '0822467809867',
            'password' => bcrypt('Rahasia123!'),
        ]);

        $user->assignRole('super-admin');
        
    }
}
