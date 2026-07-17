<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['topic_id', 'user_id', 'body', 'is_best_answer', 'upvotes', 'downvotes'];

    protected $casts = ['is_best_answer' => 'boolean'];

    public function topic(): BelongsTo  { return $this->belongsTo(Topic::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function replies(): HasMany  { return $this->hasMany(Reply::class); }
}
