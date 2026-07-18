<?php

namespace App\Services;

use App\Models\Blacklist;
use App\Models\User;
use App\Models\Warning;
use App\Notifications\ModerationNotification;

class ModerationService
{
    public function issueWarning(int $userId, string $reason, int $issuedBy, int $autoDays = 30): Warning
    {
        $warning = Warning::create([
            'user_id'   => $userId,
            'issued_by' => $issuedBy,
            'reason'    => $reason,
        ]);

        User::findOrFail($userId)->notify(new ModerationNotification('warning', $reason));

        $this->blacklistIfNeeded($userId, $issuedBy, $autoDays);

        return $warning;
    }

    public function blacklistUser(int $userId, string $reason, int $bannedBy, int $days = 30): Blacklist
    {
        $blacklist = Blacklist::create([
            'user_id'    => $userId,
            'banned_by'  => $bannedBy,
            'reason'     => $reason,
            'expires_at' => now()->addDays($days),
        ]);

        User::findOrFail($userId)->notify(new ModerationNotification('blacklist', $reason, $days));

        return $blacklist;
    }

    public function resolveWarning(int $warningId, int $resolvedBy): Warning
    {
        $warning = Warning::findOrFail($warningId);
        $warning->update(['resolved_at' => now(), 'resolved_by' => $resolvedBy]);
        return $warning;
    }

    private function blacklistIfNeeded(int $userId, int $bannedBy, int $days): void
    {
        $unresolved = Warning::where('user_id', $userId)->whereNull('resolved_at')->count();

        if ($unresolved >= 2) {
            $alreadyBanned = Blacklist::where('user_id', $userId)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();

            if (!$alreadyBanned) {
                $this->blacklistUser($userId, 'Automatic: 2 unresolved warnings', $bannedBy, $days);
            }
        }
    }
}
