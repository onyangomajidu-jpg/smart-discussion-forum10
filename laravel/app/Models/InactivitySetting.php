<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InactivitySetting extends Model
{
    protected $fillable = [
        'inactivity_days',
        'second_warning_days',
        'blacklist_after_days',
        'blacklist_duration_days',
    ];

    /** Always returns the single settings row, creating defaults if absent. */
    public static function current(): self
    {
        return self::firstOrCreate([], [
            'inactivity_days'        => 30,
            'second_warning_days'    => 7,
            'blacklist_after_days'   => 7,
            'blacklist_duration_days'=> 30,
        ]);
    }
}
