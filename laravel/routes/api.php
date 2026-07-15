<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\LoginController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;


// ── Auth (no middleware — Java client logs in here) ────────────────────
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if (!auth()->attempt($credentials)) {
        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    $user  = auth()->user();
    $token = $user->createToken('java-desktop')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role,
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    });

    Route::post('/posts',               [PostController::class, 'store']);
    Route::get('/topics/updates',       [PostController::class, 'updates']);
    Route::get('/topics/{topic}/posts', [PostController::class, 'index']);

    // Dashboard routes
    Route::get('/dashboard',  [DashboardApiController::class, 'index']);
    Route::get('/statistics', [StatisticsApiController::class, 'index']);
});