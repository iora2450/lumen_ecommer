<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@lumens.local'],
            [
                'name' => 'Administrador Lumens',
                'password' => Hash::make('lumens2026'),
                'email_verified_at' => now(),
            ]
        );

        $this->call(DemoDataSeeder::class);
    }
}