<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    // Lecturer: manage own groups
    public function index()
    {
        $groups = Group::where('created_by', auth()->id())
            ->withCount('members')
            ->orderByDesc('created_at')
            ->get();

        return view('lecturer.groups', compact('groups'));
    }

    // Student: browse & join groups
    public function studentIndex()
    {
        $user      = auth()->user();
        $joinedIds = $user->groups()->pluck('groups.id');
        $available = Group::whereNotIn('id', $joinedIds)->withCount('members')->orderBy('name')->get();
        $joined    = $user->groups()->withCount('members')->orderBy('name')->get();

        return view('groups.index', compact('available', 'joined'));
    }

    public function join(Group $group)
    {
        $user = auth()->user();
        if (!$user->groups()->where('groups.id', $group->id)->exists()) {
            $user->groups()->attach($group->id, ['role' => 'member']);
        }
        return back()->with('success', 'You joined "' . $group->name . '".');
    }

    public function leave(Group $group)
    {
        auth()->user()->groups()->detach($group->id);
        return back()->with('success', 'You left "' . $group->name . '".');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:groups,name',
            'description' => 'nullable|string|max:500',
        ]);

        Group::create([
            'name'        => $data['name'],
            'slug'        => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'created_by'  => auth()->id(),
            'is_private'  => false,
        ]);

        return back()->with('success', 'Group "' . $data['name'] . '" created successfully.');
    }

    public function destroy(Group $group)
    {
        if ($group->created_by !== auth()->id()) {
            abort(403);
        }

        $group->delete();

        return back()->with('success', 'Group deleted.');
    }
}
