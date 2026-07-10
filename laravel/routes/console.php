<?php

use App\Console\Commands\SendQuizReminders;
use App\Console\Commands\CheckInactivity;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SDD §4.2.3 — sendQuizReminder(): auto-dispatch before quiz unlock_date
Schedule::command(SendQuizReminders::class)->everyMinute();

// SDD — Automated moderation: daily inactivity check
Schedule::command(CheckInactivity::class)->daily();
