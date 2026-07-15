<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['group_id', 'user_id', 'title', 'slug', 'body', 'is_pinned', 'is_locked', 'views'];

    protected $casts = ['is_pinned' => 'boolean', 'is_locked' => 'boolean'];

    public function group(): BelongsTo  { return $this->belongsTo(Group::class); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function posts(): HasMany    { return $this->hasMany(Post::class); }
}
