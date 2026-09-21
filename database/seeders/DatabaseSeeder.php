<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with base administrative and test users.
     */
    public function run(): void
    {
        // Administrateur principal
        User::firstOrCreate(
            ['email' => 'nicolas.vdm59@gmail.com'],
            [
                'name' => 'Nicolas',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ]
        );

        // Deuxième compte : Chloé
        User::firstOrCreate(
            ['email' => 'chloe@example.com'],
            [
                'name' => 'Chloé',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ]
        );

        // Utilisateur de test
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'role' => UserRole::User,
                'email_verified_at' => now(),
            ]
        );
    }
}
