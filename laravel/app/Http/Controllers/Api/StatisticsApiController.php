<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Statistics API Controller (SDD Days 17-18 — Java GUI Statistics Panel)
 *
 * GET /api/statistics
 * Returns enriched stats for the Java desktop Statistics Panel:
 *   - User activity metrics (posts, topics, quiz attempts, avg score)
 *   - Posts-per-day for the last 7 days  → bar chart series
 *   - Quiz score distribution (buckets)  → pie chart series
 *   - Available vs attempted quizzes     → pie chart
 */
class StatisticsApiController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ── Core metrics ──────────────────────────────────────────────────
        $topicsParticipated = DB::table('posts')
            ->where('user_id', $user->id)
            ->distinct('topic_id')
            ->count('topic_id');

        $totalPosts = DB::table('posts')
            ->where('user_id', $user->id)
            ->count();

        $quizAttempts = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->count();

        $availableQuizzes = DB::table('quizzes')
            ->whereNotIn('id', function ($q) use ($user) {
                $q->select('quiz_id')
                  ->from('quiz_attempts')
                  ->where('user_id', $user->id);
            })
            ->count();

        $avgScore = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->avg('score') ?? 0;

        // ── Posts per day — last 7 days (bar chart) ───────────────────────
        $postsPerDay = DB::table('posts')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw("DATE(created_at) as day, COUNT(*) as count")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $barSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->format('Y-m-d');
            $barSeries[] = [
                'label' => now()->subDays($i)->format('D'),
                'value' => $postsPerDay->has($day) ? (int) $postsPerDay[$day]->count : 0,
            ];
        }

        // ── Score distribution (pie chart) ────────────────────────────────
        $scores = DB::table('quiz_attempts')
            ->where('user_id', $user->id)
            ->pluck('score');

        $dist = ['0-49' => 0, '50-69' => 0, '70-84' => 0, '85-100' => 0];
        foreach ($scores as $s) {
            if ($s < 50)       $dist['0-49']++;
            elseif ($s < 70)   $dist['50-69']++;
            elseif ($s < 85)   $dist['70-84']++;
            else               $dist['85-100']++;
        }

        return response()->json([
            'stats' => [
                'topicsParticipated' => $topicsParticipated,
                'totalPosts'         => $totalPosts,
                'quizAttempts'       => $quizAttempts,
                'availableQuizzes'   => $availableQuizzes,
                'avgScore'           => round((float) $avgScore, 1),
                'postsPerDay'        => $barSeries,
                'scoreDistribution'  => $dist,
            ],
        ]);
    }
}
