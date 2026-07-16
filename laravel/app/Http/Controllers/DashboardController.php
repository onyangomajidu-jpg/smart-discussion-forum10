<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $groups = $user->groups()->orderBy('name')->get();

        return view('dashboard', compact('user', 'groups'));
    }
}
