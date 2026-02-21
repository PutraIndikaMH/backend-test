<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'role'     => RoleEnum::Admin,
            ]
        );

        // Regular user
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name'     => 'User',
                'password' => Hash::make('password'),
                'role'     => RoleEnum::User,
            ]
        );

        // 5 active products
        Product::factory(5)->create();

        // 2 inactive products
        Product::factory(2)->inactive()->create();
    }
}
