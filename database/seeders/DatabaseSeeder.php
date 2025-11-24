<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the ONLY root_user - this is the only way to create root_user
        // Root user credentials should be changed immediately after first login
        User::firstOrCreate(
            ['email' => 'root@hospital.local'],
            [
                'name' => 'Root Administrator',
                'password' => 'ChangeMe123!@#', // Will be automatically hashed by User model's 'hashed' cast
                'role' => 'root_user',
                'email_verified_at' => now(),
            ]
        );

        // Optional: Create test users for development
        // User::factory(10)->create();

        // User::firstOrCreate(
        //     ['email' => 'test@example.com'],
        //     [
        //         'name' => 'Test User',
        //         'password' => Hash::make('password'),
        //         'email_verified_at' => now(),
        //     ]
        // );
    }
}
