<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends Model
{
    protected $fillable = ['user_id', 'student_id', 'programme', 'year_of_study', 'reputation'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
