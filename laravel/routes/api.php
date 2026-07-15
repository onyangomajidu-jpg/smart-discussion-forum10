<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\LoginController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;
use App\Http\Controllers\ExportController;


// ── Auth (no middleware — Java client logs in here) ────────────────────
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

<<<<<<< HEAD
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
<<<<<<< HEAD
});
=======
// Dashboard stats — accepts Bearer token (Java GUI) or web session
Route::middleware('auth:sanctum')->get('/dashboard',  [DashboardApiController::class,  'index']);
Route::middleware('auth:sanctum')->get('/statistics', [StatisticsApiController::class, 'index']);
>>>>>>> main
=======
Route::middleware('auth:sanctum')->group(function () {
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
>>>>>>> origin
=======

    // Dashboard routes
    Route::get('/dashboard',  [DashboardApiController::class, 'index']);
    Route::get('/statistics', [StatisticsApiController::class, 'index']);
});
>>>>>>> a58ff2f7328a10d06f7284d8fa3d2ac4b0e79aac
