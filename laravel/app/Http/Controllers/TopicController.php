<?php

namespace App\Http\Controllers;

use App\Services\ContentManagementService;
use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Post;

class TopicController extends Controller
{
    public function __construct(private ContentManagementService $cms) {}

    // Topics screen UI
    public function index(Request $request)
    {
        $query = Topic::with('author')->withCount('posts');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $topics = $query->latest()->get();

        $activeTopic = null;
        $posts = collect();

        if ($request->filled('topic')) {
            $activeTopic = Topic::with(['author', 'posts.author', 'posts.replies.author'])
                ->findOrFail($request->topic);
            $posts = $activeTopic->posts()->with(['author', 'replies.author'])->get();
            $activeTopic->increment('views');
        }

        return view('topics', compact('topics', 'activeTopic', 'posts'));
    }

    // createTopic()
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'body'      => 'required|string',
            'syndicate' => 'nullable|boolean',
        ]);

        // Use first group or create a default one
        $group = \App\Models\Group::firstOrCreate(
            ['slug' => 'general'],
            ['name' => 'General', 'description' => 'General discussion', 'created_by' => auth()->id()]
        );
        $data['group_id'] = $group->id;

        try {
            $topic = $this->cms->createTopic($data);
            return redirect()->route('topics.show', $topic)
                ->with('success', 'Topic created.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    public function show(Topic $topic)
    {
        $topic->load(['author', 'posts.author', 'posts.replies.author']);
        $topic->increment('views');
        return view('topics', [
            'topics'      => Topic::withCount('posts')->latest()->get(),
            'activeTopic' => $topic,
            'posts'       => $topic->posts,
        ]);
    }

    // participateDiscussion()
    public function participate(Request $request, int $topicId)
    {
        $data = $request->validate(['body' => 'required|string']);

        try {
            $this->cms->participateDiscussion($topicId, $data);
            return back()->with('success', 'Post added.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    // answerQuestion()
    public function answer(Request $request, int $postId)
    {
        $data = $request->validate([
            'body'            => 'required|string',
            'parent_reply_id' => 'nullable|exists:replies,id',
        ]);

        try {
            $this->cms->answerQuestion($postId, $data);
            return back()->with('success', 'Reply added.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    // sendNotification() - mark notifications read
    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->take(20)->get();
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json($notifications);
    }

    // deleteTopic()
    public function destroy(Topic $topic)
    {
        try {
            $this->cms->deleteTopic($topic->id);
            return redirect()->route('topics.index')->with('success', 'Topic deleted.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
