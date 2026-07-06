<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the quizzes table with full lifecycle columns (SDD §4.2 / Fig 3.12)
 * and adds the participation_records table for score storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Extend quizzes ────────────────────────────────────────────────
        Schema::table('quizzes', function (Blueprint $table) {
            $table->enum('status', ['draft', 'published', 'closed'])
                  ->default('draft')
                  ->after('description');

            // unlock_date: when students can first see the quiz
            $table->timestamp('unlock_date')->nullable()->after('status');

            // hard_deadline: absolute cut-off; auto-submit fires here
            $table->timestamp('hard_deadline')->nullable()->after('unlock_date');

            // duration in minutes (15-min timer per SDD Fig 6.4)
            $table->unsignedSmallInteger('duration_minutes')
                  ->default(15)
                  ->after('hard_deadline');

            // auto_submit: submit automatically when timer expires
            $table->boolean('auto_submit')->default(true)->after('duration_minutes');

            $table->timestamp('published_at')->nullable()->after('auto_submit');
        });

        // ── participation_records ─────────────────────────────────────────
        // Stores per-user score history and quiz engagement (SDD §4.2.5)
        Schema::create('participation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_attempt_id')->nullable()
                  ->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0.00);
            $table->enum('grade', ['A', 'B', 'C', 'D', 'F'])->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participation_records');

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'unlock_date', 'hard_deadline',
                'duration_minutes', 'auto_submit', 'published_at',
            ]);
        });
    }
};
