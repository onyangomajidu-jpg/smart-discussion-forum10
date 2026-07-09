<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Integration test user (Week 1 — Days 6-7)
        User::factory()->create([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password123'),
            'role'     => 'member',
        ]);

        // Lecturer test user
        User::factory()->create([
            'name'     => 'Test Lecturer',
            'email'    => 'lecturer@example.com',
            'password' => bcrypt('password123'),
            'role'     => 'lecturer',
        ]);

        // Admin test user
        User::factory()->create([
            'name'     => 'Test Admin',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role'     => 'administrator',
        ]);
    }
}
