<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Console\Command;

class CheckInactivity extends Command
{
    protected $signature   = 'moderation:check-inactivity';
    protected $description = 'Issue warnings to users inactive for 30+ days';

    public function handle(ModerationService $moderation): int
    {
        $adminId = User::where('role', 'admin')->value('id') ?? 1;

        $inactive = User::where('role', 'member')
            ->whereDoesntHave('posts',   fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->whereDoesntHave('replies', fn($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->get();

        foreach ($inactive as $user) {
            $alreadyWarned = $user->warnings()
                ->where('reason', 'like', '%inactivity%')
                ->whereNull('resolved_at')
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if (!$alreadyWarned) {
                $moderation->issueWarning($user->id, 'Inactivity: no participation in 30 days', $adminId);
                $this->info("Warning issued to user #{$user->id} ({$user->name})");
            }
        }

        return self::SUCCESS;
    }
}
