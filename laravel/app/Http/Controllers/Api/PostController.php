<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCreated;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /** POST /api/posts */
    public function store(Request $request)
    {
        $data = $request->validate([
            'topic_id' => 'required|exists:topics,id',
            'body'     => 'required|string',
        ]);

        $post = Post::create([
            'topic_id' => $data['topic_id'],
            'user_id'  => auth()->id(),
            'body'     => $data['body'],
        ]);

        PostCreated::dispatch($post);

        return response()->json($post->load('author'), 201);
    }

    /** GET /api/topics/{topic}/posts */
    public function index(Topic $topic)
    {
        return response()->json(
            $topic->posts()->with('author')->orderBy('created_at')->get()
        );
    }

    /** GET /api/topics/updates?since= */
    public function updates(Request $request)
    {
        $since = $request->query('since', '1970-01-01T00:00:00');

        $topics = Topic::where('updated_at', '>', $since)
            ->get(['id', 'group_id', 'title', 'body', 'is_pinned', 'is_locked', 'views', 'updated_at']);

        $posts = Post::where('updated_at', '>', $since)
            ->with('author:id,name')
            ->get()
            ->map(fn ($p) => array_merge($p->toArray(), [
                'author_name' => $p->author?->name ?? 'Unknown',
            ]));

        return response()->json([
            'topics'     => $topics,
            'posts'      => $posts,
            'fetched_at' => now()->toIso8601String(),
        ]);
    }
}
