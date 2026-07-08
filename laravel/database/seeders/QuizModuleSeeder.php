<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Lecturer;
use App\Models\Member;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * QuizModuleSeeder — seeds everything needed to run the Quiz module
 * independently before full system integration (SDD §4.2).
 *
 * Run:  php artisan db:seed --class=QuizModuleSeeder
 *
 * Creates:
 *   - 1 Lecturer  → lecturer@quiz.test  / password
 *   - 3 Students  → student1@quiz.test … student3@quiz.test  / password
 *   - 1 Group     → "Software Engineering 2024"
 *   - 2 Quizzes   → 1 published (open now), 1 draft
 *   - 5 Questions per quiz
 */
class QuizModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('participation_records')->truncate();
        DB::table('quiz_attempts')->truncate();
        DB::table('quiz_questions')->truncate();
        DB::table('quizzes')->truncate();
        DB::table('group_user')->truncate();
        DB::table('groups')->truncate();
        DB::table('lecturers')->truncate();
        DB::table('members')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Lecturer ──────────────────────────────────────────────────────
        $lecturer = User::create([
            'name'      => 'Dr. Sarah Mitchell',
            'email'     => 'lecturer@quiz.test',
            'password'  => Hash::make('password'),
            'role'      => 'lecturer',
            'is_active' => true,
        ]);
        Lecturer::create([
            'user_id'        => $lecturer->id,
            'staff_id'       => 'STAFF-001',
            'department'     => 'Computer Science',
            'specialisation' => 'Software Engineering',
        ]);

        // ── Students ──────────────────────────────────────────────────────
        $students = [];
        $studentData = [
            ['Alice Johnson', 'student1@quiz.test', 'STU-001'],
            ['Bob Williams',  'student2@quiz.test', 'STU-002'],
            ['Carol Davis',   'student3@quiz.test', 'STU-003'],
        ];
        foreach ($studentData as [$name, $email, $sid]) {
            $user = User::create([
                'name'      => $name,
                'email'     => $email,
                'password'  => Hash::make('password'),
                'role'      => 'member',
                'is_active' => true,
            ]);
            Member::create([
                'user_id'       => $user->id,
                'student_id'    => $sid,
                'programme'     => 'BSc Computer Science',
                'year_of_study' => 2,
                'reputation'    => 0,
            ]);
            $students[] = $user;
        }

        // ── Group ─────────────────────────────────────────────────────────
        $group = Group::create([
            'name'        => 'Software Engineering 2024',
            'slug'        => 'software-engineering-2024',
            'description' => 'Year 2 Software Engineering module group.',
            'created_by'  => $lecturer->id,
            'is_private'  => false,
        ]);

        // Attach lecturer + students to group
        $group->members()->attach($lecturer->id, ['role' => 'moderator']);
        foreach ($students as $s) {
            $group->members()->attach($s->id, ['role' => 'member']);
        }

        // ── Quiz 1 — Published & Open ─────────────────────────────────────
        $quiz1 = Quiz::create([
            'group_id'         => $group->id,
            'created_by'       => $lecturer->id,
            'title'            => 'Week 5: OOP Fundamentals',
            'description'      => 'Test your understanding of Object-Oriented Programming concepts covered in Week 5.',
            'status'           => 'published',
            'unlock_date'      => now()->subMinutes(5),
            'hard_deadline'    => now()->addHours(2),
            'duration_minutes' => 15,
            'auto_submit'      => true,
            'enforce_focus'    => true,
            'published_at'     => now()->subMinutes(10),
        ]);

        $q1Questions = [
            ['What does OOP stand for?',
             ['Object-Oriented Programming', 'Open Object Protocol', 'Ordered Object Processing', 'Object Operation Paradigm'],
             0, 1],
            ['Which OOP principle hides internal implementation details?',
             ['Inheritance', 'Polymorphism', 'Encapsulation', 'Abstraction'],
             2, 2],
            ['What keyword is used to inherit a class in PHP?',
             ['implements', 'extends', 'inherits', 'uses'],
             1, 1],
            ['Which concept allows a child class to override a parent method?',
             ['Encapsulation', 'Abstraction', 'Polymorphism', 'Composition'],
             2, 2],
            ['What is a constructor in OOP?',
             ['A method that destroys an object', 'A method called automatically when an object is created',
              'A static method', 'An abstract method'],
             1, 2],
        ];

        foreach ($q1Questions as [$q, $opts, $correct, $marks]) {
            QuizQuestion::create([
                'quiz_id'        => $quiz1->id,
                'question'       => $q,
                'options'        => $opts,
                'correct_option' => $correct,
                'marks'          => $marks,
            ]);
        }

        // ── Quiz 2 — Draft ────────────────────────────────────────────────
        $quiz2 = Quiz::create([
            'group_id'         => $group->id,
            'created_by'       => $lecturer->id,
            'title'            => 'Week 6: Design Patterns',
            'description'      => 'Assessment covering common software design patterns.',
            'status'           => 'draft',
            'unlock_date'      => now()->addDays(3),
            'hard_deadline'    => now()->addDays(3)->addHours(2),
            'duration_minutes' => 20,
            'auto_submit'      => true,
            'enforce_focus'    => true,
        ]);

        $q2Questions = [
            ['Which pattern ensures only one instance of a class exists?',
             ['Factory', 'Observer', 'Singleton', 'Decorator'],
             2, 2],
            ['The Observer pattern is used for?',
             ['Creating objects', 'Event-driven communication', 'Database access', 'Sorting algorithms'],
             1, 2],
            ['Which pattern adds behaviour to objects dynamically?',
             ['Singleton', 'Factory', 'Decorator', 'Proxy'],
             2, 2],
            ['MVC stands for?',
             ['Model View Controller', 'Module View Component', 'Main View Class', 'Model Variable Controller'],
             0, 1],
            ['Which pattern separates object construction from representation?',
             ['Observer', 'Builder', 'Singleton', 'Adapter'],
             1, 2],
        ];

        foreach ($q2Questions as [$q, $opts, $correct, $marks]) {
            QuizQuestion::create([
                'quiz_id'        => $quiz2->id,
                'question'       => $q,
                'options'        => $opts,
                'correct_option' => $correct,
                'marks'          => $marks,
            ]);
        }

        $this->command->info('✅ Quiz module seeded successfully!');
        $this->command->table(
            ['Role', 'Name', 'Email', 'Password'],
            [
                ['Lecturer', 'Dr. Sarah Mitchell', 'lecturer@quiz.test', 'password'],
                ['Student',  'Alice Johnson',      'student1@quiz.test', 'password'],
                ['Student',  'Bob Williams',       'student2@quiz.test', 'password'],
                ['Student',  'Carol Davis',        'student3@quiz.test', 'password'],
            ]
        );
        $this->command->info('📝 Quiz 1 (Open):  "Week 5: OOP Fundamentals"');
        $this->command->info('📝 Quiz 2 (Draft): "Week 6: Design Patterns"');
    }
}
