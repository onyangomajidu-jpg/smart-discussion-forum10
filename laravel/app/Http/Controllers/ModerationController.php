<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\User;
use App\Models\Warning;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\ModerationService;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function __construct(private ModerationService $moderation) {}

    // ── Warnings ──────────────────────────────────────────────────────────

    public function warnings()
    {
        $warnings = Warning::with(['user', 'issuer', 'resolver'])->latest()->paginate(20);
        return view('admin.moderation.warnings', compact('warnings'));
    }

    public function apiWarnings(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        $warnings = Warning::with(['user:id,name,email', 'issuer:id,name'])->latest()->get()
            ->map(fn($w) => [
                'id'          => $w->id,
                'user_id'     => $w->user_id,
                'user_name'   => $w->user?->name,
                'user_email'  => $w->user?->email,
                'issued_by'   => $w->issuer?->name,
                'reason'      => $w->reason,
                'resolved_at' => $w->resolved_at,
                'created_at'  => $w->created_at,
            ]);
        return response()->json($warnings);
    }

    public function apiIssueWarning(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason'  => 'required|string|max:255',
        ]);
        $this->moderation->issueWarning($data['user_id'], $data['reason'], $request->user()->id);
        return response()->json(['message' => 'Warning issued.']);
    }

    public function apiBlacklists(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        $blacklists = Blacklist::with(['user:id,name,email', 'banner:id,name'])->latest()->get()
            ->map(fn($b) => [
                'id'         => $b->id,
                'user_id'    => $b->user_id,
                'user_name'  => $b->user?->name,
                'user_email' => $b->user?->email,
                'banned_by'  => $b->banner?->name,
                'reason'     => $b->reason,
                'expires_at' => $b->expires_at,
                'created_at' => $b->created_at,
            ]);
        return response()->json($blacklists);
    }

    public function apiUsers(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        return response()->json(
            User::orderBy('name')->get(['id', 'name', 'email', 'role'])
        );
    }

    public function apiAdminStats(Request $request)
    {
        if ($request->user()->role !== 'admin') abort(403);
        return response()->json([
            'members'        => User::where('role', 'member')->count(),
            'lecturers'      => User::where('role', 'lecturer')->count(),
            'total_users'    => User::count(),
            'total_groups'   => \App\Models\Group::count(),
            'total_quizzes'  => \App\Models\Quiz::count(),
            'published_quizzes' => \App\Models\Quiz::where('status', 'published')->count(),
            'submissions'    => \App\Models\QuizAttempt::count(),
            'open_warnings'  => Warning::whereNull('resolved_at')->count(),
            'active_bans'    => Blacklist::where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->count(),
            'recent_users'   => User::latest()->take(8)->get(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function issueWarning(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason'  => 'required|string|max:255',
        ]);

        $this->moderation->issueWarning($data['user_id'], $data['reason'], auth()->id());

        return back()->with('success', 'Warning issued.');
    }

    public function resolveWarning(int $id)
    {
        $this->moderation->resolveWarning($id, auth()->id());
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Warning resolved.']);
        }
        return back()->with('success', 'Warning resolved.');
    }

    public function destroyWarning(int $id)
    {
        Warning::findOrFail($id)->delete();
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Warning deleted.']);
        }
        return back()->with('success', 'Warning deleted.');
    }

    // ── Blacklists ────────────────────────────────────────────────────────

    public function blacklists()
    {
        $blacklists = Blacklist::with(['user', 'banner'])->latest()->paginate(20);
        $users      = User::where('role', 'member')->orderBy('name')->get(['id', 'name']);
        return view('admin.moderation.blacklists', compact('blacklists', 'users'));
    }

    public function blacklistUser(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason'  => 'required|string|max:255',
            'days'    => 'required|integer|min:1|max:365',
        ]);

        $this->moderation->blacklistUser($data['user_id'], $data['reason'], auth()->id(), $data['days']);

        return back()->with('success', 'User blacklisted.');
    }

    public function destroyBlacklist(int $id)
    {
        Blacklist::findOrFail($id)->delete();
        return back()->with('success', 'Blacklist entry removed.');
    }

}
