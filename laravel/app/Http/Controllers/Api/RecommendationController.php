<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * displayRecommendation — surfaces personalised recommended topics
     * for the authenticated user's dashboard (AI sequence, Fig 3.13).
     *
     * GET /recommendations
     */
    public function index(Request $request, AIEngine $ai)
    {
        $userId = $request->user()->id;

        // If no recommendations stored yet, generate synchronously on first load
        $count = DB::table('ai_recommendations')->where('user_id', $userId)->count();
        if ($count === 0) {
            $ai->generateRecommendation($userId);
        }

        $recommendations = DB::table('ai_recommendations')
            ->join('topics', 'ai_recommendations.topic_id', '=', 'topics.id')
            ->where('ai_recommendations.user_id', $userId)
            ->whereNull('topics.deleted_at')
            ->select(
                'topics.id',
                'topics.title',
                'topics.slug',
                'ai_recommendations.tags',
                'ai_recommendations.score'
            )
            ->orderByDesc('ai_recommendations.score')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id'    => $r->id,
                'title' => $r->title,
                'slug'  => $r->slug,
                'tags'  => $r->tags ? explode(',', $r->tags) : [],
                'score' => $r->score,
            ]);

        return response()->json(['recommendations' => $recommendations]);
    }
}
