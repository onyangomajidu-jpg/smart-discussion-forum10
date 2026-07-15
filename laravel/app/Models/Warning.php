<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Warning extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'issued_by', 'reason', 'details', 'resolved_at', 'resolved_by'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function issuer(): BelongsTo   { return $this->belongsTo(User::class, 'issued_by'); }
    public function resolver(): BelongsTo { return $this->belongsTo(User::class, 'resolved_by'); }

    public function isResolved(): bool { return !is_null($this->resolved_at); }
}
