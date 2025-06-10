<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->delete();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@localhost',
            'password' => bcrypt('password'),
        ]);
    }
}
