<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Recommendation extends Model
{
    protected $fillable = ['user_id', 'recommendable_id', 'recommendable_type', 'score', 'generated_at'];

    protected $casts = ['generated_at' => 'datetime'];

    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function recommendable(): MorphTo  { return $this->morphTo(); }
}
