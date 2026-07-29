<?php

namespace App\Console\Commands;

use App\Models\Blacklist;
use App\Models\InactivitySetting;
use App\Models\User;
use App\Models\Warning;
use App\Services\ModerationService;
use Illuminate\Console\Command;

/**
 * Inactivity enforcement workflow:
 *
 *  1. Member has no posts/replies for `inactivity_days`
 *     → Issue Warning #1 (reason: "Inactivity warning 1/2 …")
 *
 *  2. Member still inactive `second_warning_days` after Warning #1
 *     → Issue Warning #2 (reason: "Inactivity warning 2/2 …")
 *
 *  3. Member still inactive `blacklist_after_days` after Warning #2
 *     → Auto-blacklist for `blacklist_duration_days`
 */
class CheckInactivity extends Command
{
    protected $signature   = 'moderation:check-inactivity';
    protected $description = 'Issue inactivity warnings and auto-blacklist non-compliant members';

    public function handle(ModerationService $moderation): int
    {
        $cfg     = InactivitySetting::current();
        $adminId = User::where('role', 'admin')->value('id') ?? 1;

        // Members with no activity in the base inactivity window
        $inactive = User::where('role', 'member')
            ->whereDoesntHave('posts',   fn($q) => $q->where('created_at', '>=', now()->subDays($cfg->inactivity_days)))
            ->whereDoesntHave('replies', fn($q) => $q->where('created_at', '>=', now()->subDays($cfg->inactivity_days)))
            ->get();

        foreach ($inactive as $user) {
            // Skip already-banned users
            if ($user->isBanned()) continue;

            $warnings = Warning::where('user_id', $user->id)
                ->where('reason', 'like', 'Inactivity warning%')
                ->whereNull('resolved_at')
                ->orderBy('created_at')
                ->get();

            $count = $warnings->count();

            if ($count === 0) {
                // No warnings yet → issue Warning 1
                $moderation->issueWarning(
                    $user->id,
                    "Inactivity warning 1/2: no participation in {$cfg->inactivity_days} days. "
                    . "Please post within {$cfg->second_warning_days} days to avoid a second warning.",
                    $adminId,
                    $cfg->blacklist_duration_days
                );
                $this->info("Warning 1/2 issued to {$user->name} (#{$user->id})");

            } elseif ($count === 1) {
                // Has Warning 1 — check if second_warning_days have passed
                $firstWarning = $warnings->first();
                if ($firstWarning->created_at->diffInDays(now()) >= $cfg->second_warning_days) {
                    $moderation->issueWarning(
                        $user->id,
                        "Inactivity warning 2/2: still no participation {$cfg->second_warning_days} days after first warning. "
                        . "Blacklist will be applied in {$cfg->blacklist_after_days} days if no activity.",
                        $adminId,
                        $cfg->blacklist_duration_days
                    );
                    $this->info("Warning 2/2 issued to {$user->name} (#{$user->id})");
                }

            } elseif ($count >= 2) {
                // Has 2 warnings — check if blacklist_after_days have passed since Warning 2
                $secondWarning = $warnings->skip(1)->first();
                if ($secondWarning->created_at->diffInDays(now()) >= $cfg->blacklist_after_days) {
                    $alreadyBanned = Blacklist::where('user_id', $user->id)
                        ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                        ->exists();

                    if (!$alreadyBanned) {
                        $moderation->blacklistUser(
                            $user->id,
                            "Auto-blacklisted: 2 inactivity warnings unresolved after {$cfg->blacklist_after_days} days.",
                            $adminId,
                            $cfg->blacklist_duration_days
                        );
                        $this->info("Auto-blacklisted {$user->name} (#{$user->id}) for {$cfg->blacklist_duration_days} days");
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
