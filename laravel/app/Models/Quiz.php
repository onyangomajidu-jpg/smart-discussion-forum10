<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz model — Assessment subsystem (SDD §4.2).
 *
 * Lifecycle: draft → published → closed
 */
class Quiz extends Model
{
    protected $fillable = [
        'group_id', 'created_by', 'title', 'description',
        'status', 'unlock_date', 'hard_deadline',
        'duration_minutes', 'auto_submit', 'enforce_focus', 'published_at',
        'reminder_sent_at',
        // legacy columns kept for compatibility
        'starts_at', 'ends_at',
    ];

    protected $casts = [
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'unlock_date'      => 'datetime',
        'hard_deadline'    => 'datetime',
        'published_at'     => 'datetime',
        'reminder_sent_at' => 'datetime',
        'auto_submit'      => 'boolean',
        'enforce_focus'    => 'boolean',
        'duration_minutes' => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────
    public function group(): BelongsTo              { return $this->belongsTo(Group::class); }
    public function creator(): BelongsTo            { return $this->belongsTo(User::class, 'created_by'); }
    public function questions(): HasMany            { return $this->hasMany(QuizQuestion::class); }
    public function attempts(): HasMany             { return $this->hasMany(QuizAttempt::class); }
    public function participationRecords(): HasMany { return $this->hasMany(ParticipationRecord::class); }

    // ── Scopes ────────────────────────────────────────────────────────────
    public function scopePublished($q) { return $q->where('status', 'published'); }
    public function scopeDraft($q)     { return $q->where('status', 'draft'); }
    public function scopeClosed($q)    { return $q->where('status', 'closed'); }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function isOpen(): bool
    {
        $now      = now();
        $deadline = $this->effectiveDeadline();
        return $this->status === 'published'
            && ($this->unlock_date === null || $now->gte($this->unlock_date))
            && ($deadline === null || $now->lte($deadline));
    }

    public function isPastDeadline(): bool
    {
        $deadline = $this->effectiveDeadline();
        if ($deadline === null) return false;
        if ($this->unlock_date !== null && now()->lt($this->unlock_date)) return false;
        return now()->gt($deadline);
    }

    /** Hard deadline if set, otherwise unlock_date + duration_minutes. */
    public function effectiveDeadline(): ?\Carbon\Carbon
    {
        if ($this->hard_deadline) return $this->hard_deadline;
        if ($this->unlock_date)   return $this->unlock_date->copy()->addMinutes($this->duration_minutes);
        return null;
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'published'
            && $this->unlock_date !== null
            && now()->lt($this->unlock_date)
            && !$this->isPastDeadline();
    }

    /** Seconds until unlock_date (0 if already open or no unlock date). */
    public function secondsUntilUnlock(): int
    {
        if (!$this->unlock_date || now()->gte($this->unlock_date)) return 0;
        return (int) now()->diffInSeconds($this->unlock_date);
    }

    /** Total possible marks across all questions. */
    public function totalMarks(): int
    {
        return $this->questions()->sum('marks');
    }
}
