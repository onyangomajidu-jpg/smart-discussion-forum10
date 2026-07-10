<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\User;
use App\Models\Warning;
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
        return back()->with('success', 'Warning resolved.');
    }

    public function destroyWarning(int $id)
    {
        Warning::findOrFail($id)->delete();
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
