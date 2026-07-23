<?php

namespace App\Http\Controllers;

use App\Services\ContentManagementService;
use Illuminate\Http\Request;
use App\Models\Topic;
use App\Models\Post;

class LecturerTopicController extends Controller
{
    public function __construct(private ContentManagementService $cms) {}

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
            $activeTopic = Topic::with([
                'author',
                'posts.author',
                'posts.replies.author',
                'participants',
                'blockedParticipants',
            ])->findOrFail($request->topic);

            $posts = $activeTopic->posts()->with(['author', 'replies.author'])->get();
            $activeTopic->increment('views');
        }

        return view('lecturer.topics', compact('topics', 'activeTopic', 'posts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body'  => 'required|string',
        ]);

        // Use the lecturer's first group, or fall back to general
        $group = \App\Models\Group::where('created_by', auth()->id())->first()
            ?? \App\Models\Group::firstOrCreate(
                ['slug' => 'general'],
                ['name' => 'General', 'description' => 'General discussion', 'created_by' => auth()->id()]
            );
        $data['group_id'] = $group->id;

        try {
            $topic = $this->cms->createTopic($data);
            return redirect()->route('lecturer.topics.show', $topic)
                ->with('success', 'Topic created successfully.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    public function show(Topic $topic)
    {
        $topic->load(['author', 'posts.author', 'posts.replies.author', 'participants', 'blockedParticipants']);
        $topic->increment('views');

        return view('lecturer.topics', [
            'topics'      => Topic::withCount('posts')->latest()->get(),
            'activeTopic' => $topic,
            'posts'       => $topic->posts,
        ]);
    }

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

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('audio/posts', 'public');
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('images/posts', 'public');
        }
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('files/posts', 'public');
            $data['file_name'] = $uploaded->getClientOriginalName();
            $data['file_size'] = $uploaded->getSize();
        }

        try {
            $this->cms->participateDiscussion($topicId, $data);
            return back()->with('success', 'Message sent.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    public function answer(Request $request, int $postId)
    {
        $data = $request->validate(['body' => 'required|string']);

        try {
            $this->cms->answerQuestion($postId, $data);
            return back()->with('success', 'Reply added.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }
    }

    public function destroy(Topic $topic)
    {
        try {
            $this->cms->deleteTopic($topic->id);
            return redirect()->route('lecturer.topics.index')->with('success', 'Topic deleted.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function lockTopic(Topic $topic)
    {
        $topic->update(['is_locked' => !$topic->is_locked]);
        $status = $topic->is_locked ? 'locked' : 'unlocked';
        return back()->with('success', "Topic {$status}.");
    }

    public function pinTopic(Topic $topic)
    {
        $topic->update(['is_pinned' => !$topic->is_pinned]);

        if ($topic->is_pinned) {
            $members = $topic->group->members()
                ->wherePivot('role', 'member')
                ->where('users.id', '!=', auth()->id())
                ->get();
            foreach ($members as $member) {
                $member->notify(new \App\Notifications\ModerationNotification(
                    'pinned',
                    'Topic "' . $topic->title . '" has been pinned by ' . auth()->user()->name . '.'
                ));
            }
        }

        $status = $topic->is_pinned ? 'pinned' : 'unpinned';
        return back()->with('success', "Topic {$status}.");
    }

    public function removeUser(Topic $topic, int $userId)
    {
        if ($userId === $topic->user_id) {
            return back()->withErrors(['error' => 'Cannot remove the topic creator.']);
        }
        $topic->participants()->detach($userId);
        return back()->with('success', 'Participant removed.');
    }

    public function blockUser(Topic $topic, int $userId)
    {
        if ($userId === $topic->user_id) {
            return back()->withErrors(['error' => 'Cannot block the topic creator.']);
        }
        $topic->allParticipants()->updateExistingPivot($userId, ['is_blocked' => true]);
        return back()->with('success', 'Participant blocked.');
    }

    public function unblockUser(Topic $topic, int $userId)
    {
        $topic->allParticipants()->updateExistingPivot($userId, ['is_blocked' => false]);
        return back()->with('success', 'Participant unblocked.');
    }
}
