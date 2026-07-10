<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard API Controller (SDD §3.1 — Java GUI integration)
 * 
 * Provides unified stats endpoint consumed by:
 *   - Web dashboard (JavaScript fetch)
 *   - Java GUI (ApiClient.get("/dashboard"))
 */
class DashboardApiController extends Controller
{
    /**
     * GET /api/dashboard
     * Returns aggregated user statistics
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Topic participation count
        $topicsParticipated = DB::table('posts')
            ->where('user_id', $user->id)
            ->distinct('topic_id')
            ->count('topic_id');

        // Total posts made
        $totalPosts = DB::table('posts')
            ->where('user_id', $user->id)
            ->count();

        // Quiz attempts
        $quizAttempts = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->count();

        // Available quizzes (not yet attempted)
        $availableQuizzes = DB::table('quizzes')
            ->whereNotIn('id', function ($q) use ($user) {
                $q->select('quiz_id')
                    ->from('quiz_attempts')
                    ->where('user_id', $user->id);
            })
            ->count();

        // Average quiz score
        $avgScore = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->avg('score');

        // Recent topics (last 5)
        $recentTopics = DB::table('posts')
            ->join('topics', 'posts.topic_id', '=', 'topics.id')
            ->where('posts.user_id', $user->id)
            ->select('topics.id', 'topics.title')
            ->distinct()
            ->orderBy('posts.created_at', 'desc')
            ->limit(5)
            ->get();

        // Recent quiz attempts (last 5)
        $recentAttempts = DB::table('quiz_attempts')
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->where('quiz_attempts.user_id', $user->id)
            ->select('quizzes.id', 'quizzes.title', 'quiz_attempts.score')
            ->orderBy('quiz_attempts.created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'topicsParticipated' => $topicsParticipated,
                'totalPosts' => $totalPosts,
                'quizAttempts' => $quizAttempts,
                'availableQuizzes' => $availableQuizzes,
                'avgScore' => $avgScore,
                'recentTopics' => $recentTopics,
                'recentAttempts' => $recentAttempts,
            ],
        ]);
    }
}
