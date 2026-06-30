<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SessionManager
{
    /**
     * Get current session ID
     */
    public function getCurrentSessionId(): string
    {
        return Session::getId();
    }

    /**
     * Get all active sessions for a user
     */
    public function getUserSessions(int $userId): array
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Terminate a specific session
     */
    public function terminateSession(string $sessionId): bool
    {
        return DB::table('sessions')
            ->where('id', $sessionId)
            ->delete() > 0;
    }

    /**
     * Terminate all sessions for a user except current
     */
    public function terminateOtherSessions(int $userId, string $currentSessionId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    /**
     * Terminate all sessions for a user
     */
    public function terminateAllUserSessions(int $userId): int
    {
        return DB::table('sessions')
            ->where('user_id', $userId)
            ->delete();
    }

    /**
     * Get session information
     */
    public function getSessionInfo(string $sessionId): ?object
    {
        return DB::table('sessions')
            ->where('id', $sessionId)
            ->first();
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(int $lifetimeMinutes = 120): int
    {
        $expiredTime = now()->subMinutes($lifetimeMinutes)->timestamp;
        
        return DB::table('sessions')
            ->where('last_activity', '<', $expiredTime)
            ->delete();
    }

    /**
     * Store custom session data
     */
    public function put(string $key, $value): void
    {
        Session::put($key, $value);
    }

    /**
     * Retrieve session data
     */
    public function get(string $key, $default = null)
    {
        return Session::get($key, $default);
    }

    /**
     * Remove session data
     */
    public function forget(string $key): void
    {
        Session::forget($key);
    }

    /**
     * Check if session has a key
     */
    public function has(string $key): bool
    {
        return Session::has($key);
    }

    /**
     * Flash data for next request
     */
    public function flash(string $key, $value): void
    {
        Session::flash($key, $value);
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(): bool
    {
        return Session::regenerate();
    }
}
