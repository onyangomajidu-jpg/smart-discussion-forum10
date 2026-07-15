<?php

namespace Tests\Feature\Day14;

use App\Models\Blacklist;
use App\Models\Group;
use App\Models\Topic;
use App\Models\User;
use App\Models\Warning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Day 14 — Moderation test: trigger 2 warnings → confirm blacklist enforced.
 *
 * Business rule (SDD §3.3): after a user accumulates 2 warnings the admin
 * issues a blacklist entry.  Once blacklisted the user must be denied access
 * to protected resources.
 */
class ModerationBlacklistTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $offender;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin    = User::factory()->create(['role' => 'admin']);
        $this->offender = User::factory()->create(['role' => 'member']);
    }

    // ── Warning issuance ──────────────────────────────────────────────────

    public function test_admin_can_issue_first_warning(): void
    {
        Warning::create([
            'user_id'   => $this->offender->id,
            'issued_by' => $this->admin->id,
            'reason'    => 'Inappropriate language',
        ]);

        $this->assertDatabaseHas('warnings', [
            'user_id'   => $this->offender->id,
            'issued_by' => $this->admin->id,
        ]);
        $this->assertEquals(1, Warning::where('user_id', $this->offender->id)->count());
    }

    public function test_admin_can_issue_second_warning(): void
    {
        Warning::create([
            'user_id'   => $this->offender->id,
            'issued_by' => $this->admin->id,
            'reason'    => 'Spam',
        ]);
        Warning::create([
            'user_id'   => $this->offender->id,
            'issued_by' => $this->admin->id,
            'reason'    => 'Harassment',
        ]);

        $this->assertEquals(2, Warning::where('user_id', $this->offender->id)->count());
    }

    // ── Blacklist creation after 2 warnings ───────────────────────────────

    public function test_blacklist_is_created_after_two_warnings(): void
    {
        Warning::factory()->count(2)->create([
            'user_id'   => $this->offender->id,
            'issued_by' => $this->admin->id,
        ]);

        // Admin issues blacklist once warning threshold is reached
        Blacklist::create([
            'user_id'   => $this->offender->id,
            'banned_by' => $this->admin->id,
            'reason'    => 'Accumulated 2 warnings',
            'expires_at' => now()->addDays(7),
        ]);

        $this->assertDatabaseHas('blacklists', ['user_id' => $this->offender->id]);
        $this->assertTrue($this->offender->fresh()->isBanned());
    }

    // ── Blacklist enforcement ─────────────────────────────────────────────

    public function test_banned_user_is_flagged_by_isBanned(): void
    {
        Blacklist::create([
            'user_id'    => $this->offender->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Test ban',
            'expires_at' => now()->addDays(30),
        ]);

        $this->assertTrue($this->offender->fresh()->isBanned());
    }

    public function test_expired_blacklist_does_not_ban_user(): void
    {
        Blacklist::create([
            'user_id'    => $this->offender->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Old ban',
            'expires_at' => now()->subDay(), // already expired
        ]);

        $this->assertFalse($this->offender->fresh()->isBanned());
    }

    public function test_permanent_blacklist_bans_user(): void
    {
        Blacklist::create([
            'user_id'    => $this->offender->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Permanent ban',
            'expires_at' => null,
        ]);

        $this->assertTrue($this->offender->fresh()->isBanned());
    }

    /**
     * A banned user hitting a protected route must receive 403.
     * The middleware checks isBanned() and aborts.
     */
    public function test_banned_user_is_denied_access_to_protected_route(): void
    {
        Blacklist::create([
            'user_id'    => $this->offender->id,
            'banned_by'  => $this->admin->id,
            'reason'     => 'Blacklisted',
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($this->offender->fresh())
             ->get('/quizzes')
             ->assertStatus(403);
    }
}
