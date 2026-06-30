<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = ['type', 'notifiable_type', 'notifiable_id', 'data', 'read_at'];

    protected $casts = ['data' => 'array', 'read_at' => 'datetime'];

    public function notifiable(): MorphTo { return $this->morphTo(); }

    public function markAsRead(): void { $this->update(['read_at' => now()]); }
}
