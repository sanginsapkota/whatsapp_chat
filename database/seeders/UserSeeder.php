<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'bitflux.business@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'rider@shuvakamana.test'],
            [
                'name' => 'Ram Rider',
                'password' => Hash::make('password'),
                'role' => UserRole::Rider,
                'phone' => '9800000000',
                'is_active' => true,
            ],
        );
    }
}
