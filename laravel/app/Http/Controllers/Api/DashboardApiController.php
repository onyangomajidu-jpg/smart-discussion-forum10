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

        $topicsParticipated = DB::table('topic_user')
            ->where('user_id', $user->id)
            ->count();

        // Fall back to posts-based count if topic_user is empty
        if ($topicsParticipated === 0) {
            $topicsParticipated = DB::table('posts')
                ->where('user_id', $user->id)
                ->distinct('topic_id')
                ->count('topic_id');
        }

        $totalPosts = DB::table('posts')
            ->where('user_id', $user->id)
            ->count();

        $quizAttempts = DB::table('participation_records')
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->count();

        $availableQuizzes = DB::table('quizzes')
            ->where('status', 'published')
            ->whereNotIn('id', function ($q) use ($user) {
                $q->select('quiz_id')
                    ->from('participation_records')
                    ->where('user_id', $user->id)
                    ->where('completed', true);
            })
            ->count();

        $avgScore = DB::table('participation_records')
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->avg('percentage');

        $recentTopics = DB::table('posts')
            ->join('topics', 'posts.topic_id', '=', 'topics.id')
            ->where('posts.user_id', $user->id)
            ->whereNull('topics.deleted_at')
            ->select('topics.id', 'topics.title', DB::raw('MAX(posts.created_at) as last_post_at'))
            ->groupBy('topics.id', 'topics.title')
            ->orderBy('last_post_at', 'desc')
            ->limit(5)
            ->get();

        $recentAttempts = DB::table('participation_records')
            ->join('quizzes', 'participation_records.quiz_id', '=', 'quizzes.id')
            ->where('participation_records.user_id', $user->id)
            ->where('participation_records.completed', true)
            ->select('quizzes.id', 'quizzes.title', 'participation_records.percentage as score')
            ->orderBy('participation_records.completed_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'topicsParticipated' => $topicsParticipated,
                'totalPosts'         => $totalPosts,
                'quizAttempts'       => $quizAttempts,
                'availableQuizzes'   => $availableQuizzes,
                'avgScore'           => $avgScore,
                'recentTopics'       => $recentTopics,
                'recentAttempts'     => $recentAttempts,
            ],
        ]);
    }
}
