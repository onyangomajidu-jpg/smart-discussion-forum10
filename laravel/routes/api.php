<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;
use App\Http\Controllers\ExportController;

// ── Public ping (used by Java GUI isOnline() check) ───────────────────
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

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

// ── Token-only routes (Bearer token required — Java GUI logout) ─────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', function (Request $request) {
        $token = $request->user()->currentAccessToken();
        // TransientToken (web session) has no delete() — delete by Bearer value instead
        if ($token instanceof \Laravel\Sanctum\TransientToken) {
            $bearerToken = $request->bearerToken();
            if ($bearerToken) {
                $hashed = hash('sha256', explode('|', $bearerToken, 2)[1] ?? $bearerToken);
                \Laravel\Sanctum\PersonalAccessToken::where('token', $hashed)->delete();
            }
        } else {
            $token->delete();
        }
        return response()->json(['message' => 'Logged out.']);
    });
});

// ── Session or token routes (web dashboard + Java GUI) ──────────────
Route::middleware(['auth:sanctum,web'])->group(function () {
    // Dashboard & statistics
    Route::get('/dashboard',  [DashboardApiController::class,  'index']);
    Route::get('/statistics', [StatisticsApiController::class, 'index']);

    // Posts & topics
    Route::post('/posts',               [PostController::class, 'store']);
    Route::get('/topics/updates',       [PostController::class, 'updates']);
    Route::get('/topics/{topic}/posts', [PostController::class, 'index']);
    Route::get('/topics', fn() => \App\Models\Topic::withCount('posts')->latest()->get());

    // Export & social share
    Route::get('/topics/{topicId}/export-pdf', [ExportController::class, 'exportDiscussionPDF']);
    Route::post('/posts/{postId}/share',        [ExportController::class, 'forwardToSocialMedia']);
});
