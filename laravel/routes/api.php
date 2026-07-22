<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Quiz\QuizController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// ── Session or token routes (web dashboard + Java GUI) ──────────────────
Route::middleware(['auth:sanctum,web'])->group(function () {
    // Dashboard & statistics
    Route::get('/dashboard',  [DashboardApiController::class,  'index']);
    Route::get('/statistics', [StatisticsApiController::class, 'index']);

    // Posts & topics
    Route::post('/posts',               [PostController::class, 'store']);
    Route::put('/posts/{postId}',        [PostController::class, 'update']);
    Route::delete('/posts/{postId}',     [PostController::class, 'destroy']);
    Route::get('/topics/updates',       [PostController::class, 'updates']);
    Route::get('/topics/{topic}/posts', [PostController::class, 'index']);
    Route::get('/topics', fn() => \App\Models\Topic::withCount('posts')->latest()->get());
    Route::post('/topics',              [\App\Http\Controllers\TopicController::class, 'store']);
    Route::delete('/topics/{topic}',    [\App\Http\Controllers\TopicController::class, 'destroy']);

    // Replies
    Route::post('/posts/{postId}/reply', [PostController::class, 'reply']);
    Route::get('/posts/{postId}/replies', [PostController::class, 'replies']);

    // Topic actions (pin/lock — lecturer; block/unblock/remove — topic owner or admin)
    Route::post('/topics/{topic}/pin',                          [PostController::class, 'pinTopic']);
    Route::post('/topics/{topic}/lock',                         [PostController::class, 'lockTopic']);
    Route::post('/topics/{topic}/users/{userId}/block',         [PostController::class, 'blockUser']);
    Route::post('/topics/{topic}/users/{userId}/unblock',       [PostController::class, 'unblockUser']);
    Route::delete('/topics/{topic}/users/{userId}',             [PostController::class, 'removeUser']);

    // Profile
    Route::get('/profile',  [ProfileController::class, 'apiShow']);
    Route::put('/profile',  [ProfileController::class, 'apiUpdate']);

    // AI Recommendations
    Route::get('/recommendations', [RecommendationController::class, 'index']);

    // Groups
    Route::get('/groups',                  [GroupController::class, 'apiIndex']);
    Route::post('/groups/{group}/join',    [GroupController::class, 'join']);
    Route::delete('/groups/{group}/leave', [GroupController::class, 'leave']);

    // Export & social share
    Route::get('/topics/{topicId}/export-pdf', [ExportController::class, 'exportDiscussionPDF']);
    Route::post('/posts/{postId}/share',        [ExportController::class, 'forwardToSocialMedia']);

    // Notifications
    Route::get('/notifications', [\App\Http\Controllers\TopicController::class, 'notifications']);

    // ── Student quiz routes ───────────────────────────────────────────────
    Route::get('/quizzes',                    [QuizController::class, 'apiIndex']);
    Route::get('/quizzes/{quiz}',             [QuizController::class, 'apiShow']);
    Route::post('/quizzes/{quiz}/submit',     [QuizController::class, 'apiSubmit']);
    Route::get('/quizzes/{quiz}/result',      [QuizController::class, 'apiResult']);

    // ── Lecturer quiz routes ──────────────────────────────────────────────
    Route::get('/lecturer/analytics',              [\App\Http\Controllers\StatisticsController::class, 'apiLecturerAnalytics']);
    Route::get('/lecturer/quizzes',                  [QuizController::class, 'apiLecturerIndex']);
    Route::post('/lecturer/quizzes',                 [QuizController::class, 'apiStore']);
    Route::post('/lecturer/quizzes/{quiz}/publish',  [QuizController::class, 'apiPublish']);
    Route::post('/lecturer/quizzes/{quiz}/remind',   [QuizController::class, 'remind']);
    Route::get('/lecturer/quizzes/{quiz}/results',   [QuizController::class, 'apiResults']);
    Route::delete('/lecturer/quizzes/{quiz}',        [QuizController::class, 'destroy']);
    Route::get('/lecturer/my-groups',               [GroupController::class, 'apiMyGroups']);
    Route::post('/lecturer/groups',                  [GroupController::class, 'apiStore']);
    Route::delete('/lecturer/groups/{group}',        [GroupController::class, 'apiDestroy']);
    Route::get('/lecturer/analytics',                [StatisticsController::class, 'apiLecturerAnalytics']);

    // ── Admin moderation routes ───────────────────────────────────────────
    Route::get('/admin/dashboard',              [ModerationController::class, 'apiAdminStats']);
    Route::get('/admin/warnings',                [ModerationController::class, 'apiWarnings']);
    Route::post('/admin/warnings',               [ModerationController::class, 'apiIssueWarning']);
    Route::patch('/admin/warnings/{id}/resolve', [ModerationController::class, 'resolveWarning']);
    Route::delete('/admin/warnings/{id}',        [ModerationController::class, 'destroyWarning']);
    Route::get('/admin/blacklists',              [ModerationController::class, 'apiBlacklists']);
    Route::post('/admin/blacklists',             [ModerationController::class, 'blacklistUser']);
    Route::delete('/admin/blacklists/{id}',      [ModerationController::class, 'destroyBlacklist']);
    Route::get('/admin/users',                   [ModerationController::class, 'apiUsers']);
    Route::get('/admin/stats',                   [ModerationController::class, 'apiAdminStats']);
});
