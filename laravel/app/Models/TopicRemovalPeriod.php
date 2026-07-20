<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopicRemovalPeriod extends Model
{
    protected $fillable = ['topic_id', 'user_id', 'removed_at', 'restored_at'];
    protected $casts = ['removed_at' => 'datetime', 'restored_at' => 'datetime'];
}
