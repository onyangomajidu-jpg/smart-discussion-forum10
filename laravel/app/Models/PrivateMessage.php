<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateMessage extends Model
{
    use SoftDeletes;
    protected $fillable = ['sender_id', 'recipient_id', 'body', 'audio_path', 'image_path', 'file_path', 'file_name', 'file_size', 'read_at', 'reply_to_id'];

    public function replyTo(): BelongsTo { return $this->belongsTo(PrivateMessage::class, 'reply_to_id'); }

    protected $casts = ['read_at' => 'datetime'];

    public function sender(): BelongsTo    { return $this->belongsTo(User::class, 'sender_id'); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_id'); }

    /**
     * Scope to all messages exchanged between two specific users, in either
     * direction — this is what makes up a single 1:1 conversation thread.
     */
    public function scopeBetween(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('recipient_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('recipient_id', $userA);
        });
    }
}
