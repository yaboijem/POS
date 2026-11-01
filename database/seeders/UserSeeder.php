<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Maria Santos',
                'email' => 'admin@karinderya.test',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        User::query()->updateOrCreate(
            ['username' => 'cashier'],
            [
                'name' => 'Jose Cruz',
                'email' => 'cashier@karinderya.test',
                'password' => 'password',
                'role' => User::ROLE_CASHIER,
                'is_active' => true,
            ]
        );
    }
}
