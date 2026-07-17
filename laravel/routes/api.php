<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;
use App\Http\Controllers\ExportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Connectivity probe — no auth required
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

// ── Auth ──────────────────────────────────────────────────────────────────
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

// ── Protected routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    });

    // Posts & topics
    Route::post('/posts',               [PostController::class, 'store']);
    Route::get('/topics/updates',       [PostController::class, 'updates']);
    Route::get('/topics/{topic}/posts', [PostController::class, 'index']);

    // Dashboard & statistics
    Route::get('/dashboard',  [DashboardApiController::class,  'index']);
    Route::get('/statistics', [StatisticsApiController::class, 'index']);

    // Topics list — used by ExportWindow to populate topic picker
    Route::get('/topics', fn() => \App\Models\Topic::withCount('posts')->latest()->get());

    // exportDiscussionPDF(topicId) — streams PDF; Java GUI calls this
    Route::get('/topics/{topicId}/export-pdf', [ExportController::class, 'exportDiscussionPDF']);

    // forwardToSocialMedia(postId, platform)
    Route::post('/posts/{postId}/share', [ExportController::class, 'forwardToSocialMedia']);
});
