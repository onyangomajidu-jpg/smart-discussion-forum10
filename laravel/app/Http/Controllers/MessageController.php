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

        $messages = PrivateMessage::withTrashed()->with('replyTo')->between($me, $other->id)->orderBy('created_at')->get();

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
            'body'       => 'nullable|string',
            'audio'      => 'nullable|file|mimes:webm,ogg,mp4,wav,mp3|max:10240',
            'image'      => 'nullable|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'file'       => 'nullable|file|max:20480',
            'reply_to_id'=> 'nullable|integer|exists:private_messages,id',
        ]);

        if (!$request->filled('body') && !$request->hasFile('audio') && !$request->hasFile('image') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => 'Please enter a message or attach a file.']);
        }

        $data = [
            'sender_id'    => $me,
            'recipient_id' => $other->id,
            'body'         => $request->input('body', ''),
            'reply_to_id'  => $request->input('reply_to_id'),
        ];

        $disk = config('filesystems.default');

        if ($request->hasFile('audio')) {
            $data['audio_path'] = $request->file('audio')->store('audio/messages', $disk);
        }
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('images/messages', $disk);
        }
        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $data['file_path'] = $uploaded->store('files/messages', $disk);
            $data['file_name'] = $uploaded->getClientOriginalName();
            $data['file_size'] = $uploaded->getSize();
        }

        $message = PrivateMessage::create($data);

        $other->notify(new PrivateMessageNotification($message));

        return redirect()->route('messages.show', $other->id);
    }

    // Poll for new messages since a given timestamp (used by live chat)
    public function poll(Request $request, int $userId)
    {
        $me    = auth()->id();
        $other = User::findOrFail($userId);
        $since = $request->query('since', '1970-01-01T00:00:00');

        $messages = PrivateMessage::withTrashed()
            ->with('replyTo')
            ->between($me, $other->id)
            ->where('created_at', '>', $since)
            ->orderBy('created_at')
            ->get()
            ->map(fn($msg) => [
                'id'          => $msg->id,
                'sender_id'   => $msg->sender_id,
                'body'        => $msg->trashed() ? null : $msg->body,
                'deleted'     => $msg->trashed(),
                'image_path'  => $msg->image_path,
                'audio_path'  => $msg->audio_path,
                'file_path'   => $msg->file_path,
                'file_name'   => $msg->file_name,
                'file_size'   => $msg->file_size,
                'reply_to'    => $msg->replyTo ? [
                    'id'        => $msg->replyTo->id,
                    'sender_id' => $msg->replyTo->sender_id,
                    'body'      => $msg->replyTo->body,
                ] : null,
                'created_at'  => $msg->created_at->toIso8601String(),
            ]);

        // Mark incoming as read
        PrivateMessage::where('sender_id', $other->id)
            ->where('recipient_id', $me)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['messages' => $messages, 'fetched_at' => now()->toIso8601String()]);
    }

    public function update(Request $request, int $id)
    {
        $msg = PrivateMessage::where('id', $id)->where('sender_id', auth()->id())->firstOrFail();
        $msg->update(['body' => $request->validate(['body' => 'required|string'])['body']]);
        return response()->json(['success' => true, 'body' => $msg->body]);
    }

    public function destroy(int $id)
    {
        $msg = PrivateMessage::where('id', $id)->where('sender_id', auth()->id())->firstOrFail();
        $msg->delete(); // soft-delete: files are preserved on disk
        return response()->json(['success' => true]);
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
