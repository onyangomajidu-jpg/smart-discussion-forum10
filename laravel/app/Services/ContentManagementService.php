<?php

namespace App\Services;

use App\Contracts\IContentManagement;
use App\Events\NewPost;
use App\Events\NewReply;
use App\Models\Post;
use App\Models\Reply;
use App\Models\Topic;
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

        broadcast(new NewPost($topic->id, Auth::id(), $data['body'], 'topic'))->toOthers();

        return $topic;
    }

    public function participateDiscussion(int $topicId, array $data): Post
    {
        if ($this->filterContent($data['body'] ?? '')) {
            throw new \RuntimeException('Content flagged as spam.');
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

        $post = Post::findOrFail($postId);

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
