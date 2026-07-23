<?php

namespace App\Http\Controllers\Api;

use App\Events\PostCreated;
use App\Http\Controllers\Controller;
use App\Models\Blacklist;
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

        // Check global admin ban
        $banned = Blacklist::where('user_id', auth()->id())
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
        if ($banned) {
            return response()->json(['message' => 'You have been banned from the forum.'], 403);
        }

        // Check topic-level block
        $topic = Topic::find($data['topic_id']);
        $entry = $topic->allParticipants()->wherePivot('user_id', auth()->id())->first();
        if ($entry && $entry->pivot->is_blocked) {
            return response()->json(['message' => 'You have been blocked from this topic.'], 403);
        }

        $post = Post::create([
            'topic_id' => $data['topic_id'],
            'user_id'  => auth()->id(),
            'body'     => $data['body'],
        ]);

        PostCreated::dispatch($post);

        return response()->json($post->load('author'), 201);
    }

    /** GET /api/topics/{topic}/posts */
    public function index(Request $request, Topic $topic)
    {
        $query = $topic->posts()->with('author')->orderBy('created_at');

        if ($request->filled('since')) {
            $query->where('created_at', '>', $request->query('since'));
        }

        return response()->json(
            $query->get()->map(fn($p) => array_merge($p->toArray(), [
                'author_name' => $p->author?->name ?? 'User',
            ]))
        );
    }

    /** POST /api/posts/{postId}/reply */
    public function reply(Request $request, int $postId)
    {
        $data = $request->validate(['body' => 'required|string']);
        $banned = Blacklist::where('user_id', auth()->id())
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
        if ($banned) return response()->json(['message' => 'You have been banned.'], 403);

        $post = \App\Models\Post::findOrFail($postId);
        $reply = \App\Models\Reply::create([
            'post_id' => $postId,
            'user_id' => auth()->id(),
            'body'    => $data['body'],
        ]);
        if ($post->author && $post->author->id !== auth()->id()) {
            $post->author->notify(new \App\Notifications\AnswerNotification($reply));
        }
        return response()->json($reply->load('author'), 201);
    }

    /** GET /api/posts/{postId}/replies */
    public function replies(int $postId)
    {
        $replies = \App\Models\Reply::where('post_id', $postId)
            ->with('author:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'post_id'     => $r->post_id,
                'user_id'     => $r->user_id,
                'author_name' => $r->author?->name ?? 'Unknown',
                'body'        => $r->body,
                'created_at'  => $r->created_at,
            ]);
        return response()->json($replies);
    }

    /** PUT /api/posts/{postId} */
    public function update(Request $request, int $postId)
    {
        $post = Post::findOrFail($postId);
        if ($post->user_id !== auth()->id() && auth()->user()->role !== 'admin') abort(403);
        $post->update($request->validate(['body' => 'required|string']));
        return response()->json($post);
    }

    /** DELETE /api/posts/{postId} */
    public function destroy(int $postId)
    {
        $post = Post::findOrFail($postId);
        if ($post->user_id !== auth()->id() && auth()->user()->role !== 'admin') abort(403);
        $post->delete();
        return response()->json(['message' => 'Deleted.']);
    }

    /** POST /api/topics/{topic}/pin */
    public function pinTopic(Topic $topic)
    {
        if (!in_array(auth()->user()->role, ['lecturer', 'admin'])) abort(403);
        $topic->update(['is_pinned' => !$topic->is_pinned]);

        if ($topic->is_pinned) {
            // Notify all students in the topic's group
            $students = $topic->group->members()
                ->where('role', 'member')
                ->where('users.id', '!=', auth()->id())
                ->get();
            foreach ($students as $student) {
                $student->notify(new \App\Notifications\ModerationNotification(
                    'pinned',
                    "Topic \"" . $topic->title . "\" has been pinned by " . auth()->user()->name . "."
                ));
            }
        }

        return response()->json(['is_pinned' => $topic->is_pinned]);
    }

    /** POST /api/topics/{topic}/lock */
    public function lockTopic(Topic $topic)
    {
        if (!in_array(auth()->user()->role, ['lecturer', 'admin'])) abort(403);
        $topic->update(['is_locked' => !$topic->is_locked]);
        return response()->json(['is_locked' => $topic->is_locked]);
    }

    /** POST /api/topics/{topic}/users/{userId}/block */
    public function blockUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !in_array(auth()->user()->role, ['admin'])) abort(403);
        $topic->allParticipants()->syncWithoutDetaching([$userId => ['is_blocked' => true]]);
        return response()->json(['message' => 'User blocked.']);
    }

    /** POST /api/topics/{topic}/users/{userId}/unblock */
    public function unblockUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !in_array(auth()->user()->role, ['admin'])) abort(403);
        $topic->allParticipants()->updateExistingPivot($userId, ['is_blocked' => false]);
        return response()->json(['message' => 'User unblocked.']);
    }

    /** DELETE /api/topics/{topic}/users/{userId} */
    public function removeUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !in_array(auth()->user()->role, ['admin'])) abort(403);
        if ($userId === $topic->user_id) return response()->json(['message' => 'Cannot remove creator.'], 422);
        $topic->participants()->detach($userId);
        return response()->json(['message' => 'User removed.']);
    }

    /** GET /api/topics/updates?since= */
    public function updates(Request $request)
    {
        $since = $request->query('since', '1970-01-01T00:00:00');

        $topics = Topic::where('updated_at', '>', $since)
            ->with('author:id,name')
            ->get(['id', 'group_id', 'user_id', 'title', 'body', 'is_pinned', 'is_locked', 'views', 'updated_at'])
            ->map(fn($t) => array_merge($t->toArray(), [
                'author_name' => $t->author?->name ?? 'Unknown',
            ]));

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
