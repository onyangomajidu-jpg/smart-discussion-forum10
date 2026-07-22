<?php

namespace App\Http\Controllers;

use App\Models\PrivateMessage;
use App\Models\User;
use App\Notifications\PrivateMessageNotification;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // Inbox — conversation list on the left, no thread open yet.
    public function index(Request $request)
    {
        $me = auth()->id();

        return view('messages', [
            'conversations' => $this->conversationsFor($me),
            'searchResults' => $this->searchResults($request, $me),
            'other'         => null,
            'messages'      => collect(),
        ]);
    }

    // Inbox with a specific 1:1 conversation thread opened on the right,
    // fully separate from any topic/group chat.
    public function show(Request $request, int $userId)
    {
        $me = auth()->id();

        if ($userId === $me) {
            return redirect()->route('messages.index')->withErrors(['body' => "You can't message yourself."]);
        }

        $other = User::findOrFail($userId);

        $messages = PrivateMessage::between($me, $other->id)->orderBy('created_at')->get();

        // Mark anything they sent us as read now that we've opened the thread.
        PrivateMessage::where('sender_id', $other->id)
            ->where('recipient_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('messages', [
            'conversations' => $this->conversationsFor($me),
            'searchResults' => $this->searchResults($request, $me),
            'other'         => $other,
            'messages'      => $messages,
        ]);
    }

    // Send a text and/or voice message to another user.
    public function store(Request $request, int $userId)
    {
        $me = auth()->id();

        if ($userId === $me) {
            return back()->withErrors(['body' => "You can't message yourself."]);
        }

        $other = User::findOrFail($userId);

        $request->validate([
            'body'  => 'nullable|string',
            'audio' => 'nullable|file|mimes:webm,ogg,mp4,wav,mp3|max:10240',
        ]);

        if (!$request->filled('body') && !$request->hasFile('audio')) {
            return back()->withErrors(['body' => 'Please enter a message or record audio.']);
        }

        $data = [
            'sender_id'    => $me,
            'recipient_id' => $other->id,
            'body'         => $request->input('body', ''),
        ];

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('audio/messages', 'public');
        }

        $message = PrivateMessage::create($data);

        $other->notify(new PrivateMessageNotification($message));

        return redirect()->route('messages.show', $other->id);
    }

    // Total unread private-message count — used for the nav badge.
    public static function unreadCountFor(int $userId): int
    {
        return PrivateMessage::where('recipient_id', $userId)->whereNull('read_at')->count();
    }

    // Every distinct conversation the given user is part of, most recently
    // active first, each with its last message and unread count.
    private function conversationsFor(int $me)
    {
        $partnerIds = PrivateMessage::query()
            ->where('sender_id', $me)->orWhere('recipient_id', $me)
            ->get(['sender_id', 'recipient_id'])
            ->flatMap(fn ($m) => [$m->sender_id, $m->recipient_id])
            ->unique()
            ->reject(fn ($id) => $id == $me)
            ->values();

        return User::whereIn('id', $partnerIds)->get()->map(function ($user) use ($me) {
            $last = PrivateMessage::between($me, $user->id)->latest()->first();
            $unread = PrivateMessage::where('sender_id', $user->id)
                ->where('recipient_id', $me)
                ->whereNull('read_at')
                ->count();

            return [
                'user'      => $user,
                'last'      => $last,
                'unread'    => $unread,
                'last_time' => $last?->created_at,
            ];
        })->sortByDesc('last_time')->values();
    }

    // "Start a new conversation" search results (excluding self).
    private function searchResults(Request $request, int $me)
    {
        if (!$request->filled('search')) {
            return collect();
        }

        return User::where('id', '!=', $me)
            ->where('name', 'like', '%' . $request->search . '%')
            ->limit(20)
            ->get();
    }
}
