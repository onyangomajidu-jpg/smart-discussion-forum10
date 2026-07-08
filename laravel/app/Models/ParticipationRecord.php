<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ParticipationRecord — stores per-user quiz score and engagement
 * (SDD §4.2.5 — participationRecord).
 */
class ParticipationRecord extends Model
{
    protected $fillable = [
        'quiz_id', 'user_id', 'quiz_attempt_id',
        'score', 'max_score', 'percentage', 'grade',
        'completed', 'completed_at',
    ];

    protected $casts = [
        'completed'    => 'boolean',
        'completed_at' => 'datetime',
        'percentage'   => 'decimal:2',
    ];

    // ── Relations ─────────────────────────────────────────────────────────
    public function quiz(): BelongsTo        { return $this->belongsTo(Quiz::class); }
    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function attempt(): BelongsTo     { return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id'); }

    // ── Grade helper ──────────────────────────────────────────────────────
    public static function gradeFromPercentage(float $pct): string
    {
        return match(true) {
            $pct >= 80 => 'A',
            $pct >= 65 => 'B',
            $pct >= 50 => 'C',
            $pct >= 40 => 'D',
            default    => 'F',
        };
    }
}
