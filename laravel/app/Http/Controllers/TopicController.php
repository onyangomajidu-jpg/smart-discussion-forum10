<?php

namespace App\Http\Controllers;

use App\Services\ContentManagementService;
use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Post;
use App\Models\TopicRemovalPeriod;

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

        $userGroupIds = auth()->user()->groups()->pluck('groups.id');

        // Get lecturer IDs whose groups the student has joined
        $lecturerIds = \App\Models\Group::whereIn('id', $userGroupIds)->pluck('created_by');

        // Temporary debug — remove after fix
        if (request()->has('debug_pin')) {
            dd([
                'user_id'       => auth()->id(),
                'userGroupIds'  => $userGroupIds->toArray(),
                'lecturerIds'   => $lecturerIds->toArray(),
                'pinned_topics' => Topic::where('is_pinned', true)->get()->map(fn($t) => ['id'=>$t->id,'title'=>$t->title,'group_id'=>$t->group_id,'user_id'=>$t->user_id])->toArray(),
            ]);
        }

        $topics = $query->latest()->get()
            ->sortByDesc(fn($t) => $t->is_pinned && (
                $userGroupIds->contains($t->group_id) ||
                $lecturerIds->contains($t->user_id)
            ))
            ->values();

        $activeTopic = null;
        $posts = collect();

        if ($request->filled('topic')) {
            $activeTopic = Topic::with(['author', 'posts.author', 'posts.replies.author', 'participants', 'blockedParticipants', 'removedParticipants'])
                ->findOrFail($request->topic);
            $posts = collect();
            $isRemoved = $activeTopic->removedParticipants->contains(auth()->id());
            if (!$isRemoved) {
                $posts = $this->filterPostsForUser($activeTopic, auth()->id());
            }
            $activeTopic->increment('views');
        }

        return view('topics', compact('topics', 'activeTopic', 'posts', 'userGroupIds', 'lecturerIds'));
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
            ['name' => 'General', 'description' => 'General discussion', 'created_by' => auth()->id(), 'is_private' => false]
        );
        $data['group_id'] = $group->id;

        try {
            $topic = $this->cms->createTopic($data);
            if ($request->expectsJson()) {
                return response()->json($topic, 201);
            }
            return redirect()->route('topics.show', $topic)
                ->with('success', 'Topic created.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    public function show(Topic $topic)
    {
        $topic->load(['author', 'posts.author', 'posts.replies.author', 'participants', 'blockedParticipants', 'removedParticipants']);
        $topic->increment('views');
        $isRemoved = $topic->removedParticipants->contains(auth()->id());
        $userGroupIds  = auth()->user()->groups()->pluck('groups.id');
        $lecturerIds   = \App\Models\Group::whereIn('id', $userGroupIds)->pluck('created_by');
        return view('topics', [
            'topics'       => Topic::withCount('posts')->latest()->get()
                ->sortByDesc(fn($t) => $t->is_pinned && (
                    $userGroupIds->contains($t->group_id) ||
                    $lecturerIds->contains($t->user_id)
                ))
                ->values(),
            'activeTopic'  => $topic,
            'posts'        => $isRemoved ? collect() : $this->filterPostsForUser($topic, auth()->id()),
            'userGroupIds' => $userGroupIds,
            'lecturerIds'  => $lecturerIds,
        ]);
    }

    // participateDiscussion()
    public function participate(Request $request, int $topicId)
    {
        $request->validate([
            'body'  => 'nullable|string',
            'audio' => 'nullable|file|mimes:webm,ogg,mp4,wav,mp3|max:10240',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'file'  => 'nullable|file|max:20480',
        ]);

        if (!$request->filled('body') && !$request->hasFile('audio') && !$request->hasFile('image') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => 'Please enter a message or attach a file.']);
        }

        $data = ['body' => $request->input('body', '')];

        $disk = config('filesystems.default');
        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('audio/posts', $disk);
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('images/posts', $disk);
        }
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('files/posts', $disk);
            $data['file_name'] = $uploaded->getClientOriginalName();
            $data['file_size'] = $uploaded->getSize();
        }

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

    // sendNotification() - fetch notifications then mark as read
    public function notifications()
    {
        $user          = auth()->user();
        $notifications = $user->notifications()->latest()->take(20)->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => data_get($n->data, 'type', 'info'),
                'message'    => data_get($n->data, 'message', ''),
                'read'       => !is_null($n->read_at),
                'created_at' => $n->created_at,
            ]);
        $user->unreadNotifications()->update(['read_at' => now()]);
        return response()->json($notifications);
    }

    // deleteTopic()
    public function destroy(Request $request, Topic $topic)
    {
        try {
            $this->cms->deleteTopic($topic->id);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Topic deleted.']);
            }
            return redirect()->route('topics.index')->with('success', 'Topic deleted.');
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // removeUser() — only topic creator can remove participants
    public function removeUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }
        if ($userId === $topic->user_id) {
            return back()->withErrors(['error' => 'Cannot remove the topic creator.']);
        }
        $topic->allParticipants()->updateExistingPivot($userId, ['is_removed' => true]);
        TopicRemovalPeriod::create([
            'topic_id'   => $topic->id,
            'user_id'    => $userId,
            'removed_at' => now(),
        ]);
        return back()->with('success', 'User removed from topic.');
    }

    // unremoveUser() — restore a removed participant
    public function unremoveUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $topic->allParticipants()->updateExistingPivot($userId, ['is_removed' => false]);
        TopicRemovalPeriod::where('topic_id', $topic->id)
            ->where('user_id', $userId)
            ->whereNull('restored_at')
            ->update(['restored_at' => now()]);
        return back()->with('success', 'User restored to topic.');
    }

    private function filterPostsForUser(Topic $topic, int $userId): \Illuminate\Support\Collection
    {
        $latestRestoration = TopicRemovalPeriod::where('topic_id', $topic->id)
            ->where('user_id', $userId)
            ->whereNotNull('restored_at')
            ->latest('restored_at')
            ->value('restored_at');

        $query = $topic->posts()->with(['author', 'replies.author']);

        if ($latestRestoration) {
            $query->where('created_at', '>=', $latestRestoration);
        }

        return $query->get();
    }

    // blockUser() — only topic creator can block participants
    public function blockUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }
        if ($userId === $topic->user_id) {
            return back()->withErrors(['error' => 'Cannot block the topic creator.']);
        }
        $topic->allParticipants()->updateExistingPivot($userId, ['is_blocked' => true]);
        return back()->with('success', 'User blocked from topic.');
    }

    // unblockUser() — only topic creator can unblock
    public function unblockUser(Topic $topic, int $userId)
    {
        if (auth()->id() !== $topic->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }
        $topic->allParticipants()->updateExistingPivot($userId, ['is_blocked' => false]);
        return back()->with('success', 'User unblocked.');
    }
}
