<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds reminder_sent_at to quizzes so sendQuizReminder() is dispatched
 * exactly once per quiz (SDD §4.2.3 — Fig 3.12 step 3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('reminder_sent_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
    }
};
