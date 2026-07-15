<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExportController extends Controller
{
    /**
     * exportDiscussionPDF(topicId)
     * Retrieve full thread, format content, generate and stream PDF.
     */
    public function exportDiscussionPDF(int $topicId)
    {
        $topic = Topic::with([
            'author',
            'group',
            'posts' => fn($q) => $q->withTrashed(false)->with([
                'author',
                'replies' => fn($r) => $r->with('author'),
            ]),
        ])->findOrFail($topicId);

        $pdf = Pdf::loadView('exports.discussion-pdf', compact('topic'))
            ->setPaper('a4', 'portrait')
            ->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => false]);

        $filename = 'discussion-' . $topic->id . '-' . str($topic->title)->slug() . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * forwardToSocialMedia(postId, platform)
     * Connect to platform API, share content, confirm.
     * Supported platforms: twitter, linkedin, facebook
     */
    public function forwardToSocialMedia(Request $request, int $postId)
    {
        $request->validate([
            'platform' => 'required|in:twitter,linkedin,facebook',
        ]);

        $post     = Post::with(['author', 'topic'])->findOrFail($postId);
        $platform = $request->platform;
        $text     = $this->buildShareText($post);
        $url      = config('app.url') . '/topics?topic=' . $post->topic_id;

        $result = match ($platform) {
            'twitter'  => $this->shareTwitter($text, $url),
            'linkedin' => $this->shareLinkedIn($text, $url),
            'facebook' => $this->shareFacebook($text, $url),
        };

        return response()->json([
            'shared'   => true,
            'platform' => $platform,
            'message'  => $result,
            'url'      => $url,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildShareText(Post $post): string
    {
        $snippet = str($post->body)->limit(200)->toString();
        return "💬 \"{$snippet}\" — {$post->author->name} on SmartForum";
    }

    private function shareTwitter(string $text, string $url): string
    {
        $token = config('services.twitter.bearer_token');
        if (!$token) return 'Twitter not configured — share URL: ' . $url;

        $response = Http::withToken($token)
            ->post('https://api.twitter.com/2/tweets', [
                'text' => $text . ' ' . $url,
            ]);

        return $response->successful()
            ? 'Tweeted successfully (id: ' . $response->json('data.id') . ')'
            : 'Twitter error: ' . $response->body();
    }

    private function shareLinkedIn(string $text, string $url): string
    {
        $token = config('services.linkedin.access_token');
        $urn   = config('services.linkedin.author_urn'); // urn:li:person:xxx
        if (!$token || !$urn) return 'LinkedIn not configured — share URL: ' . $url;

        $response = Http::withToken($token)
            ->post('https://api.linkedin.com/v2/ugcPosts', [
                'author'          => $urn,
                'lifecycleState'  => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary'  => ['text' => $text],
                        'shareMediaCategory' => 'ARTICLE',
                        'media' => [[
                            'status'      => 'READY',
                            'originalUrl' => $url,
                        ]],
                    ],
                ],
                'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
            ]);

        return $response->successful()
            ? 'Posted to LinkedIn successfully'
            : 'LinkedIn error: ' . $response->body();
    }

    private function shareFacebook(string $text, string $url): string
    {
        $token  = config('services.facebook.page_access_token');
        $pageId = config('services.facebook.page_id');
        if (!$token || !$pageId) return 'Facebook not configured — share URL: ' . $url;

        $response = Http::post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
            'message'      => $text,
            'link'         => $url,
            'access_token' => $token,
        ]);

        return $response->successful()
            ? 'Posted to Facebook (id: ' . $response->json('id') . ')'
            : 'Facebook error: ' . $response->body();
    }
}
