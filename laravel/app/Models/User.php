<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'avatar', 'bio', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ── Role helpers ───────────────────────────────────────
    public function isMember(): bool   { return $this->role === 'member'; }
    public function isLecturer(): bool { return $this->role === 'lecturer'; }
    public function isAdmin(): bool    { return $this->role === 'admin'; }

    // ── Profile relations ───────────────────────────────
    public function member(): HasOne      { return $this->hasOne(Member::class); }
    public function lecturer(): HasOne    { return $this->hasOne(Lecturer::class); }
    public function admin(): HasOne       { return $this->hasOne(Admin::class); }

    // ── Forum relations ────────────────────────────────
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withPivot('role');
    }

    public function topics(): HasMany        { return $this->hasMany(Topic::class); }
    public function posts(): HasMany         { return $this->hasMany(Post::class); }
    public function replies(): HasMany       { return $this->hasMany(Reply::class); }
    public function warnings(): HasMany      { return $this->hasMany(Warning::class); }
    public function blacklists(): HasMany    { return $this->hasMany(Blacklist::class); }
    public function recommendations(): HasMany { return $this->hasMany(Recommendation::class); }

    // ── Blacklist check ───────────────────────────────
    public function isBanned(): bool
    {
        return $this->blacklists()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
