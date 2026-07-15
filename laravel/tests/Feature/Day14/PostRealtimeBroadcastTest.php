<?php

namespace Tests\Feature\Day14;

use App\Events\PostCreated;
use App\Models\Group;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Day 14 — End-to-end test: post message on web → broadcast event fired.
 *
 * Verifies that when a member submits a post via the web, the Post record
 * is persisted and a broadcast-compatible event is dispatched so the Java
 * desktop client (subscribed via WebSocket) can receive it in real time.
 */
class PostRealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    private User  $member;
    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();

        $lecturer = User::factory()->create(['role' => 'lecturer']);
        $group    = Group::factory()->create(['created_by' => $lecturer->id]);

        $this->member = User::factory()->create(['role' => 'member']);
        $group->members()->attach($this->member->id);

        $this->topic = Topic::factory()->create([
            'group_id' => $group->id,
            'user_id'  => $lecturer->id,
        ]);
    }

    /** POST /api/posts persists the message and returns 201. */
    public function test_post_is_stored_in_database(): void
    {
        $this->actingAs($this->member)
             ->postJson('/api/posts', [
                 'topic_id' => $this->topic->id,
                 'body'     => 'Hello from the web client',
             ])
             ->assertStatus(201)
             ->assertJsonFragment(['body' => 'Hello from the web client']);

        $this->assertDatabaseHas('posts', [
            'topic_id' => $this->topic->id,
            'user_id'  => $this->member->id,
            'body'     => 'Hello from the web client',
        ]);
    }

    /** Broadcast event is dispatched when a post is created. */
    public function test_broadcast_event_is_dispatched_on_post_creation(): void
    {
        Event::fake();

        $this->actingAs($this->member)
             ->postJson('/api/posts', [
                 'topic_id' => $this->topic->id,
                 'body'     => 'Realtime message',
             ]);

        Event::assertDispatched(PostCreated::class, function (PostCreated $e) {
            return $e->post->body === 'Realtime message';
        });
    }

    /** GET /api/topics/{id}/posts returns the newly created post. */
    public function test_new_post_appears_in_topic_feed(): void
    {
        Post::factory()->create([
            'topic_id' => $this->topic->id,
            'user_id'  => $this->member->id,
            'body'     => 'Visible on desktop',
        ]);

        $this->actingAs($this->member)
             ->getJson("/api/topics/{$this->topic->id}/posts")
             ->assertStatus(200)
             ->assertJsonFragment(['body' => 'Visible on desktop']);
    }
}
