<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $groups = $user->groups()->orderBy('name')->get();

        $quizAnnouncements  = [];
        $quizModalTriggers  = [];
        if ($user->role === 'member') {
            $allPending = Quiz::published()
                ->whereHas('group.members', fn ($q) => $q->where('users.id', $user->id))
                ->where(fn ($q) => $q->whereNull('hard_deadline')->orWhere('hard_deadline', '>', now()))
                ->with('group')
                ->orderBy('unlock_date')
                ->get();

            // Banner: upcoming quizzes where lecturer has sent a reminder
            $quizAnnouncements = $allPending
                ->filter(fn ($q) => $q->isUpcoming() && !is_null($q->reminder_sent_at))
                ->values();
        }

        return view('dashboard', compact('user', 'groups', 'quizAnnouncements'));
    }
}
