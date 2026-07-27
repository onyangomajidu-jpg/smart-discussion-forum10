<?php

namespace App\Services;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AIEngine
{
    // Keyword taxonomy used as the ML classifier
    private array $taxonomy = [
        'programming'  => ['code', 'function', 'class', 'algorithm', 'debug', 'php', 'python', 'java', 'javascript'],
        'database'     => ['sql', 'query', 'database', 'table', 'migration', 'eloquent', 'orm'],
        'networking'   => ['http', 'api', 'rest', 'request', 'response', 'socket', 'tcp', 'ip'],
        'mathematics'  => ['equation', 'calculus', 'algebra', 'matrix', 'statistics', 'probability'],
        'security'     => ['auth', 'token', 'password', 'encrypt', 'hash', 'vulnerability', 'xss', 'csrf'],
        'web'          => ['html', 'css', 'laravel', 'framework', 'blade', 'route', 'controller', 'view'],
    ];

    /**
     * classifyTopic — ML classifier: tags a topic based on its content.
     * Returns array of matched tags with confidence scores.
     */
    public function classifyTopic(string $topicContent): array
    {
        $text   = strtolower($topicContent);
        $scores = [];

        foreach ($this->taxonomy as $tag => $keywords) {
            $hits = 0;
            foreach ($keywords as $kw) {
                $hits += substr_count($text, $kw);
            }
            if ($hits > 0) {
                $scores[$tag] = min(round($hits / 10, 2), 1.0);
            }
        }

        arsort($scores);
        return $scores; // ['programming' => 0.8, 'web' => 0.5, ...]
    }

    /**
     * generateRecommendation — retrieves user activity history, runs ML
     * classification, stores personalised topic list.
     */
    public function generateRecommendation(int $memberId): void
    {
        // 1. Build user interest profile from activity history
        $activityTopics = DB::table('posts')
            ->join('topics', 'posts.topic_id', '=', 'topics.id')
            ->where('posts.user_id', $memberId)
            ->whereNull('topics.deleted_at')
            ->select('topics.id', 'topics.title', 'topics.body')
            ->distinct()
            ->get();

        // Aggregate tags from topics the user already engaged with
        $interestProfile = [];
        foreach ($activityTopics as $topic) {
            $tags = $this->classifyTopic($topic->title . ' ' . $topic->body);
            foreach ($tags as $tag => $score) {
                $interestProfile[$tag] = ($interestProfile[$tag] ?? 0) + $score;
            }
        }

        if (empty($interestProfile)) {
            $this->storeColdStartRecommendations($memberId);
            return;
        }

        arsort($interestProfile);
        $topInterests = array_slice(array_keys($interestProfile), 0, 3);

        // 2. Score all topics (not just unparticipated ones)
        $allTopics = DB::table('topics')
            ->whereNull('deleted_at')
            ->select('id', 'title', 'body')
            ->get();

        $participatedIds = $activityTopics->pluck('id')->all();
        $stored = 0;

        // First pass: topics the user has NOT participated in
        foreach ($allTopics->whereNotIn('id', $participatedIds) as $topic) {
            $tags  = $this->classifyTopic($topic->title . ' ' . $topic->body);
            $score = 0;
            foreach ($topInterests as $interest) {
                $score += $tags[$interest] ?? 0;
            }
            if ($score > 0) {
                $this->upsertRecommendation($memberId, $topic->id, array_keys($tags), $score);
                $stored++;
            }
        }

        // Second pass: if fewer than 5 found, fill with participated topics scored by interest
        if ($stored < 5) {
            foreach ($allTopics->whereIn('id', $participatedIds) as $topic) {
                $tags  = $this->classifyTopic($topic->title . ' ' . $topic->body);
                $score = 0;
                foreach ($topInterests as $interest) {
                    $score += $tags[$interest] ?? 0;
                }
                $this->upsertRecommendation($memberId, $topic->id, array_keys($tags), max($score, 0.1));
                $stored++;
                if ($stored >= 5) break;
            }
        }

        // Third pass: if still nothing, cold-start
        if ($stored === 0) {
            $this->storeColdStartRecommendations($memberId);
        }
    }

    private function storeColdStartRecommendations(int $memberId): void
    {
        $popular = DB::table('topics')
            ->whereNull('deleted_at')
            ->orderByDesc('views')
            ->limit(5)
            ->select('id', 'title', 'body')
            ->get();

        foreach ($popular as $topic) {
            $tags = $this->classifyTopic($topic->title . ' ' . $topic->body);
            $this->upsertRecommendation($memberId, $topic->id, $tags ? array_keys($tags) : ['general'], 0.1);
        }
    }

    private function upsertRecommendation(int $userId, int $topicId, array $tags, float $score): void
    {
        DB::table('ai_recommendations')->upsert(
            [
                'user_id'    => $userId,
                'topic_id'   => $topicId,
                'tags'       => implode(',', $tags),
                'score'      => round(min($score, 1.0), 4),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            ['user_id', 'topic_id'],
            ['tags', 'score', 'updated_at']
        );
    }
}
