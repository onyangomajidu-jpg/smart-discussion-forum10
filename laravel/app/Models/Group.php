<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'created_by', 'is_private'];

    protected $casts = ['is_private' => 'boolean'];

    public function creator(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }
    public function members(): BelongsToMany { return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps(); }
    public function topics(): HasMany        { return $this->hasMany(Topic::class); }
    public function quizzes(): HasMany       { return $this->hasMany(Quiz::class); }
}
