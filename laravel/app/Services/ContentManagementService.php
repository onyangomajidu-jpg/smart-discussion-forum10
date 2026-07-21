<?php

namespace App\Services;

use App\Contracts\IContentManagement;
use App\Events\NewPost;
use App\Events\NewReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ContentManagementService implements IContentManagement
{
    private array $spamKeywords = [
        'buy now', 'click here', 'free money', 'make money fast',
        'casino', 'viagra', 'crypto giveaway', 'earn $', 'limited offer',
    ];

    public function filterContent(string $content): bool
    {
        $lower = strtolower($content);
        foreach ($this->spamKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true; // is spam
            }
        }
        // Block excessive URLs (>3 links = likely spam)
        return substr_count($lower, 'http') > 3;
    }

    public function createTopic(array $data): Topic
    {
        if ($this->filterContent($data['body'] ?? '')) {
            throw new \RuntimeException('Content flagged as spam.');
        }

        $topic = Topic::create([
            'group_id' => $data['group_id'],
            'user_id'  => Auth::id(),
            'title'    => $data['title'],
            'slug'     => Str::slug($data['title']) . '-' . Str::random(5),
            'body'     => $data['body'],
        ]);

        // Track creator as first participant
        $topic->participants()->syncWithoutDetaching([Auth::id() => ['is_blocked' => false]]);

        broadcast(new NewPost($topic->id, Auth::id(), $data['body'], 'topic'))->toOthers();

        return $topic;
    }

    public function participateDiscussion(int $topicId, array $data): Post
    {
        if ($this->filterContent($data['body'] ?? '')) {
            throw new \RuntimeException('Content flagged as spam.');
        }

        // Check global admin ban
        $banned = \App\Models\Blacklist::where('user_id', Auth::id())
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
        if ($banned) {
            throw new \RuntimeException('You have been banned from the forum.');
        }

        $topic = Topic::find($topicId);
        if ($topic) {
            $entry = $topic->belongsToMany(User::class, 'topic_user')
                ->withPivot('is_blocked', 'is_removed')
                ->wherePivot('user_id', Auth::id())
                ->first();
            if (!$entry) {
                // First time posting — auto-join as participant
                $topic->allParticipants()->syncWithoutDetaching([Auth::id() => ['is_blocked' => false, 'is_removed' => false]]);
            } else {
                if ($entry->pivot->is_removed) {
                    throw new \RuntimeException('You have been removed from this topic.');
                }
                if ($entry->pivot->is_blocked) {
                    throw new \RuntimeException('You have been blocked from this topic.');
                }
            }
        }

        $post = Post::create([
            'topic_id' => $topicId,
            'user_id'  => Auth::id(),
            'body'     => $data['body'],
        ]);

        broadcast(new NewPost($topicId, Auth::id(), $data['body'], 'post'))->toOthers();

        return $post;
    }

    public function answerQuestion(int $postId, array $data): Reply
    {
        if ($this->filterContent($data['body'] ?? '')) {
            throw new \RuntimeException('Content flagged as spam.');
        }

        // Check global admin ban
        $banned = \App\Models\Blacklist::where('user_id', Auth::id())
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
        if ($banned) {
            throw new \RuntimeException('You have been banned from the forum.');
        }

        $post = Post::findOrFail($postId);

        // Check topic-level removal or block
        $entry = $post->topic->allParticipants()
            ->wherePivot('user_id', Auth::id())
            ->first();
        if (!$entry) {
            throw new \RuntimeException('You have been removed from this topic.');
        }
        if ($entry->pivot->is_removed) {
            throw new \RuntimeException('You have been removed from this topic.');
        }
        if ($entry->pivot->is_blocked) {
            throw new \RuntimeException('You have been blocked from this topic.');
        }

        $reply = Reply::create([
            'post_id'         => $postId,
            'user_id'         => Auth::id(),
            'parent_reply_id' => $data['parent_reply_id'] ?? null,
            'body'            => $data['body'],
        ]);

        broadcast(new NewReply($postId, Auth::id(), $data['body']))->toOthers();

        // Notify topic author if different user
        if ($post->author && $post->author->id !== Auth::id()) {
            $post->author->notify(new \App\Notifications\AnswerNotification($reply));
        }

        return $reply;
    }

    public function editPost(int $postId, array $data): Post
    {
        if ($this->filterContent($data['body'] ?? '')) {
            throw new \RuntimeException('Content flagged as spam.');
        }

        $post = Post::findOrFail($postId);
        $this->authorizeOwner($post->user_id);
        $post->update(['body' => $data['body']]);

        return $post;
    }

    public function deletePost(int $postId): bool
    {
        $post = Post::findOrFail($postId);
        $this->authorizeOwner($post->user_id);
        return (bool) $post->delete();
    }

    public function deleteTopic(int $topicId): bool
    {
        $topic = Topic::findOrFail($topicId);
        $this->authorizeOwner($topic->user_id);
        return (bool) $topic->delete();
    }

    private function authorizeOwner(int $ownerId): void
    {
        $user = Auth::user();
        if ($user->id !== $ownerId && !$user->isAdmin()) {
            throw new \RuntimeException('Unauthorized.');
        }
    }
}
