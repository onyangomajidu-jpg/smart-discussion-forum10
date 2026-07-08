<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuizAttempt extends Model
{
    protected $fillable = ['quiz_id', 'user_id', 'answers', 'score', 'submitted_at'];

    protected $casts = [
        'answers'      => 'array',
        'submitted_at' => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────
    public function quiz(): BelongsTo               { return $this->belongsTo(Quiz::class); }
    public function user(): BelongsTo               { return $this->belongsTo(User::class); }
    public function participationRecord(): HasOne   { return $this->hasOne(ParticipationRecord::class); }

    // ── Helpers ───────────────────────────────────────────────────────────
    public function isSubmitted(): bool { return $this->submitted_at !== null; }
}
