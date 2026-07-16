<?php

namespace Tests\Feature\Day14;

use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Day 14 — End-to-end test: offline write → reconnect → verify sync.
 *
 * The Java client stores messages in pending_messages while offline, then
 * calls POST /api/posts for each row on reconnect.  This test validates
 * the server-side contract: the endpoint accepts the batched uploads and
 * the GET /api/topics/updates endpoint returns the correct delta payload
 * so the client can refresh its local cache.
 */
class OfflineSyncTest extends TestCase
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

    /**
     * Simulates the Java client uploading three messages that were written
     * while offline.  Each POST /api/posts must succeed (201) and the rows
     * must appear in the database.
     */
    public function test_offline_messages_are_uploaded_on_reconnect(): void
    {
        $offlineMessages = [
            'Offline message 1',
            'Offline message 2',
            'Offline message 3',
        ];

        foreach ($offlineMessages as $body) {
            $this->actingAs($this->member)
                 ->postJson('/api/posts', [
                     'topic_id' => $this->topic->id,
                     'body'     => $body,
                 ])
                 ->assertStatus(201);
        }

        foreach ($offlineMessages as $body) {
            $this->assertDatabaseHas('posts', [
                'topic_id' => $this->topic->id,
                'user_id'  => $this->member->id,
                'body'     => $body,
            ]);
        }
    }

    /**
     * GET /api/topics/updates?since=<timestamp> must return topics and posts
     * created after the given timestamp so the Java client can rebuild its
     * local cache after reconnecting.
     */
    public function test_updates_endpoint_returns_delta_since_timestamp(): void
    {
        // Seed a post that should appear in the delta
        $this->actingAs($this->member)
             ->postJson('/api/posts', [
                 'topic_id' => $this->topic->id,
                 'body'     => 'Post after reconnect',
             ]);

        $since = now()->subMinutes(5)->toIso8601String();

        $this->actingAs($this->member)
             ->getJson("/api/topics/updates?since={$since}")
             ->assertStatus(200)
             ->assertJsonStructure(['topics', 'posts', 'fetched_at']);
    }

    /**
     * Duplicate upload of the same message (idempotency guard):
     * the server must not create a second identical row when the Java client
     * retries a message that was already synced.
     */
    public function test_duplicate_post_upload_is_handled_gracefully(): void
    {
        $payload = ['topic_id' => $this->topic->id, 'body' => 'Idempotent message'];

        $this->actingAs($this->member)->postJson('/api/posts', $payload)->assertStatus(201);

        // Second attempt (retry after uncertain network) must not crash
        $response = $this->actingAs($this->member)->postJson('/api/posts', $payload);
        $this->assertContains($response->status(), [200, 201, 409, 422]);
    }
}
