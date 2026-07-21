<?php

namespace App\Http\Controllers;


use App\Services\AIEngine;

use App\Models\Quiz;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, AIEngine $ai)
    {
        $user   = $request->user();
        $groups = $user->groups()->orderBy('name')->get();
        $uid    = $user->id;


        // KPI cards
        $topicsJoined = DB::table('topic_user')->where('user_id', $uid)->count();
        if ($topicsJoined === 0) {
            $topicsJoined = DB::table('posts')->where('user_id', $uid)->distinct('topic_id')->count('topic_id');
        }
        $postsMade    = DB::table('posts')->where('user_id', $uid)->count();
        $quizAttempts = DB::table('participation_records')->where('user_id', $uid)->where('completed', true)->count();
        $avgScore     = DB::table('participation_records')->where('user_id', $uid)->where('completed', true)->avg('percentage');

        // Topic participation panel — last 5 topics the user posted in
        $recentTopics = DB::table('posts')
            ->join('topics', 'posts.topic_id', '=', 'topics.id')
            ->where('posts.user_id', $uid)
            ->whereNull('topics.deleted_at')
            ->select('topics.id', 'topics.title')
            ->distinct()
            ->orderByDesc('posts.created_at')
            ->limit(5)
            ->get();

        // Quiz attempts panel — last 5 completed attempts
        $recentAttempts = DB::table('participation_records')
            ->join('quizzes', 'participation_records.quiz_id', '=', 'quizzes.id')
            ->where('participation_records.user_id', $uid)
            ->where('participation_records.completed', true)
            ->select('quizzes.id', 'quizzes.title', 'participation_records.percentage as score')
            ->orderByDesc('participation_records.completed_at')
            ->limit(5)
            ->get();

        // Statistics review bars
        $availableQuizzes = DB::table('quizzes')
            ->where('status', 'published')
            ->whereNotIn('id', function ($q) use ($uid) {
                $q->select('quiz_id')->from('participation_records')
                  ->where('user_id', $uid)->where('completed', true);
            })
            ->count();
        $engPct  = min($postsMade * 5, 100);
        $total   = $quizAttempts + $availableQuizzes;
        $compPct = $total > 0 ? round($quizAttempts * 100 / $total) : 0;
        $avgPct  = $avgScore !== null ? round($avgScore) : 0;

        // AI recommendations
        $ai->generateRecommendation($uid);
        $recommendations = DB::table('ai_recommendations')
            ->join('topics', 'ai_recommendations.topic_id', '=', 'topics.id')
            ->where('ai_recommendations.user_id', $uid)
            ->whereNull('topics.deleted_at')
            ->select('topics.id', 'topics.title', 'ai_recommendations.tags', 'ai_recommendations.score')
            ->orderByDesc('ai_recommendations.score')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'    => $r->id,
                'title' => $r->title,
                'tags'  => $r->tags ? explode(',', $r->tags) : [],
                'score' => $r->score,
            ]);

        return view('dashboard', compact(
            'user', 'groups',
            'topicsJoined', 'postsMade', 'quizAttempts', 'avgScore',
            'recentTopics', 'recentAttempts',
            'engPct', 'compPct', 'avgPct',
            'recommendations'
        ));

        $quizAnnouncements  = [];
        $quizModalTriggers  = [];
        if ($user->role === 'member') {
            $allPending = Quiz::published()
                ->whereHas('group.members', fn ($q) => $q->where('users.id', $user->id))
                ->where(fn ($q) => $q->whereNull('hard_deadline')->orWhere('hard_deadline', '>', now()))
                ->with('group')
                ->orderBy('unlock_date')
                ->get();

            // Banner: upcoming quizzes where lecturer has sent a reminder
            $quizAnnouncements = $allPending
                ->filter(fn ($q) => $q->isUpcoming() && !is_null($q->reminder_sent_at))
                ->values();
        }

        return view('dashboard', compact('user', 'groups', 'quizAnnouncements'));

    }
}
