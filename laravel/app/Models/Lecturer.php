<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lecturer extends Model
{
    protected $fillable = ['user_id', 'staff_id', 'department', 'specialisation'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
