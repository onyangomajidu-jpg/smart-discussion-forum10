<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Member;
use App\Models\Lecturer;
use App\Models\Admin;

class AuthenticationSeeder extends Seeder
{
    /**
     * Seed test users for authentication system
     */
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('forum_rules_acceptances')->truncate();
        DB::table('admins')->truncate();
        DB::table('lecturers')->truncate();
        DB::table('members')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create test users with forum rules acceptance
        $this->createMemberUser();
        $this->createLecturerUser();
        $this->createAdminUser();

        $this->command->info('✅ Authentication test users created successfully!');
        $this->command->info('');
        $this->command->info('Test Credentials:');
        $this->command->info('─────────────────────────────────────────');
        $this->command->info('Member:    student@example.com / password');
        $this->command->info('Lecturer:  lecturer@example.com / password');
        $this->command->info('Admin:     admin@example.com / password');
        $this->command->info('─────────────────────────────────────────');
    }

    protected function createMemberUser(): void
    {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'member',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Member::create([
            'user_id' => $user->id,
            'student_id' => 'STU001',
            'programme' => 'BSc Computer Science',
            'year_of_study' => 2,
            'reputation' => 100,
        ]);

        DB::table('forum_rules_acceptances')->insert([
            'user_id' => $user->id,
            'accepted_at' => now(),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createLecturerUser(): void
    {
        $user = User::create([
            'name' => 'Dr. Jane Smith',
            'email' => 'lecturer@example.com',
            'password' => Hash::make('password'),
            'role' => 'lecturer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Lecturer::create([
            'user_id' => $user->id,
            'staff_id' => 'LEC001',
            'department' => 'Computer Science',
            'specialisation' => 'Software Engineering',
        ]);

        DB::table('forum_rules_acceptances')->insert([
            'user_id' => $user->id,
            'accepted_at' => now(),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createAdminUser(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Admin::create([
            'user_id' => $user->id,
            'super_admin' => true,
        ]);

        DB::table('forum_rules_acceptances')->insert([
            'user_id' => $user->id,
            'accepted_at' => now(),
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
