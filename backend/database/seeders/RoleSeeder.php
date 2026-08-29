<?php

namespace Database\Seeders;

use App\Enums\RoleSlug;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'slug' => RoleSlug::ADMIN,
                'name' => 'Administrator',
                'description' => 'Pengurus sistem PESGO dan pemantau transaksi',
            ],
            [
                'slug' => RoleSlug::SHOPPER,
                'name' => 'Personal Shopper',
                'description' => 'Penyedia servis belian peribadi dan pesanan kumpulan',
            ],
            [
                'slug' => RoleSlug::CUSTOMER,
                'name' => 'Customer',
                'description' => 'Pengguna pembeli biasa dan penyertai pesanan kumpulan',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                ]
            );
        }
    }
}
