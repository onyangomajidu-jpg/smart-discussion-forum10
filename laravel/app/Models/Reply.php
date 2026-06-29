<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reply extends Model
{
    use SoftDeletes;

    protected $fillable = ['post_id', 'user_id', 'parent_reply_id', 'body'];

    public function post(): BelongsTo       { return $this->belongsTo(Post::class); }
    public function author(): BelongsTo     { return $this->belongsTo(User::class, 'user_id'); }
    public function parent(): BelongsTo     { return $this->belongsTo(Reply::class, 'parent_reply_id'); }
    public function children(): HasMany     { return $this->hasMany(Reply::class, 'parent_reply_id'); }
}
