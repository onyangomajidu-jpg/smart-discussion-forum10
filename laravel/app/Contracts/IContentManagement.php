<?php

namespace App\Contracts;

use App\Models\Post;
use App\Models\Topic;

interface IContentManagement
{
    public function createTopic(array $data): Topic;
    public function participateDiscussion(int $topicId, array $data): Post;
    public function answerQuestion(int $postId, array $data): \App\Models\Reply;
    public function editPost(int $postId, array $data): Post;
    public function deletePost(int $postId): bool;
    public function filterContent(string $content): bool;
}
