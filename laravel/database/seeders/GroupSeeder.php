<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Group;
use App\Models\User;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $lecturer = User::where('email', 'lecturer@example.com')->first();
        $student  = User::where('email', 'student@example.com')->first();

        if (!$lecturer) {
            $this->command->warn('Run AuthenticationSeeder first.');
            return;
        }

        $groups = [
            ['name' => 'CS101 — Intro to Programming',   'slug' => 'cs101-intro-programming',   'description' => 'First-year programming fundamentals.'],
            ['name' => 'CS201 — Data Structures',         'slug' => 'cs201-data-structures',      'description' => 'Arrays, linked lists, trees and graphs.'],
            ['name' => 'CS301 — Software Engineering',    'slug' => 'cs301-software-engineering', 'description' => 'SDLC, design patterns and agile methods.'],
            ['name' => 'CS401 — Database Systems',        'slug' => 'cs401-database-systems',     'description' => 'Relational databases, SQL and NoSQL.'],
        ];

        foreach ($groups as $data) {
            $group = Group::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['created_by' => $lecturer->id])
            );

            // Attach lecturer as admin member
            if (!$group->members()->where('users.id', $lecturer->id)->exists()) {
                $group->members()->attach($lecturer->id, ['role' => 'admin']);
            }

            // Attach student as member
            if ($student && !$group->members()->where('users.id', $student->id)->exists()) {
                $group->members()->attach($student->id, ['role' => 'member']);
            }
        }

        $this->command->info('✅ Groups seeded: ' . count($groups) . ' groups created.');
    }
}
