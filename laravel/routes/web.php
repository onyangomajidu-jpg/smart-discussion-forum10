<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Quiz\QuizController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ProfileController;

// ── Guest Routes ───────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// ── Authenticated Routes ───────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // AI Recommendations — displayRecommendation() (AI sequence, Fig 3.13)
    Route::get('/recommendations', [App\Http\Controllers\Api\RecommendationController::class, 'index'])->name('recommendations');

    // Dashboard (All authenticated users)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Reports export — available to all authenticated users
    Route::get('/reports/export', [StatisticsController::class, 'export'])->name('reports.export');
});

// ── Member Routes ──────────────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\MemberMiddleware::class])->group(function () {
    Route::get('/quiz/live-check', [QuizController::class, 'liveCheck'])->name('quizzes.live-check');

    // Student quiz routes (SDD §4.2 — Student quiz screen Fig 6.6)
    Route::get('/quizzes',                [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}',         [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/quizzes/{quiz}/submit', [QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('/quizzes/{quiz}/result',  [QuizController::class, 'result'])->name('quizzes.result');

    // Analytics — Statistics screen (SDD §4.3 / Fig 6.5)
    Route::get('/analytics', [StatisticsController::class, 'index'])->name('analytics.index');

    // Student group routes
    Route::get('/groups',                    [GroupController::class, 'studentIndex'])->name('groups.index');
    Route::post('/groups/{group}/join',      [GroupController::class, 'join'])->name('groups.join');
    Route::delete('/groups/{group}/leave',   [GroupController::class, 'leave'])->name('groups.leave');
});

// ── Export & Social Sharing (Week 3) ─────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/topics/{topicId}/export-pdf', [App\Http\Controllers\ExportController::class, 'exportDiscussionPDF'])->name('topics.export-pdf');
    Route::post('/posts/{postId}/share',       [App\Http\Controllers\ExportController::class, 'forwardToSocialMedia'])->name('posts.share');
});

// ── Topics / Content Management Routes ────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/topics', [App\Http\Controllers\TopicController::class, 'index'])->name('topics.index');
    Route::post('/topics', [App\Http\Controllers\TopicController::class, 'store'])->name('topics.store');
    Route::get('/topics/{topic}', [App\Http\Controllers\TopicController::class, 'show'])->name('topics.show');
    Route::delete('/topics/{topic}', [App\Http\Controllers\TopicController::class, 'destroy'])->name('topics.destroy');
    Route::delete('/topics/{topic}/users/{userId}', [App\Http\Controllers\TopicController::class, 'removeUser'])->name('topics.removeUser');
    Route::post('/topics/{topic}/users/{userId}/block', [App\Http\Controllers\TopicController::class, 'blockUser'])->name('topics.blockUser');
    Route::post('/topics/{topic}/users/{userId}/unblock', [App\Http\Controllers\TopicController::class, 'unblockUser'])->name('topics.unblockUser');
    Route::post('/topics/{topicId}/participate', [App\Http\Controllers\TopicController::class, 'participate'])->name('topics.participate');
    Route::post('/posts/{postId}/answer', [App\Http\Controllers\TopicController::class, 'answer'])->name('topics.answer');
    Route::put('/posts/{id}', [App\Http\Controllers\PostController::class, 'update'])->name('posts.update');
    Route::delete('/posts/{id}', [App\Http\Controllers\PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/notifications', [App\Http\Controllers\TopicController::class, 'notifications'])->name('notifications.index');
});

// ── Lecturer Routes ────────────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\LecturerMiddleware::class])->group(function () {
    Route::get('/lecturer/dashboard', function () {
        return view('lecturer.dashboard');
    })->name('lecturer.dashboard');

    // Lecturer analytics — evaluation roster + compliance (SDD §4.3 / Fig 6.4)
    Route::get('/lecturer/analytics', [StatisticsController::class, 'lecturerAnalytics'])->name('lecturer.analytics');

    // Lecturer quiz management routes (SDD §4.2 — Lecturer quiz screen Fig 6.4)
    Route::get('/lecturer/quizzes',                 [QuizController::class, 'lecturerIndex'])->name('lecturer.quizzes.index');
    Route::get('/lecturer/quizzes/create',          [QuizController::class, 'create'])->name('lecturer.quizzes.create');
    Route::post('/lecturer/quizzes',                [QuizController::class, 'store'])->name('lecturer.quizzes.store');
    Route::get('/lecturer/quizzes/{quiz}',          [QuizController::class, 'show'])->name('lecturer.quizzes.show');
    Route::get('/lecturer/quizzes/{quiz}/edit',     [QuizController::class, 'edit'])->name('lecturer.quizzes.edit');
    Route::post('/lecturer/quizzes/{quiz}/update',  [QuizController::class, 'update'])->name('lecturer.quizzes.update');
    Route::post('/lecturer/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('lecturer.quizzes.publish');
    Route::post('/lecturer/quizzes/{quiz}/remind',  [QuizController::class, 'remind'])->name('lecturer.quizzes.remind');
    Route::get('/lecturer/quizzes/{quiz}/results',  [QuizController::class, 'results'])->name('lecturer.quizzes.results');
    Route::delete('/lecturer/quizzes/{quiz}',        [QuizController::class, 'destroy'])->name('lecturer.quizzes.destroy');

    // Lecturer group management
    Route::get('/lecturer/groups',              [GroupController::class, 'index'])->name('lecturer.groups.index');
    Route::post('/lecturer/groups',             [GroupController::class, 'store'])->name('lecturer.groups.store');
    Route::delete('/lecturer/groups/{group}',   [GroupController::class, 'destroy'])->name('lecturer.groups.destroy');

    // Lecturer topic participation panel
    Route::get('/lecturer/topics',                                          [App\Http\Controllers\LecturerTopicController::class, 'index'])->name('lecturer.topics.index');
    Route::post('/lecturer/topics',                                         [App\Http\Controllers\LecturerTopicController::class, 'store'])->name('lecturer.topics.store');
    Route::get('/lecturer/topics/{topic}',                                  [App\Http\Controllers\LecturerTopicController::class, 'show'])->name('lecturer.topics.show');
    Route::delete('/lecturer/topics/{topic}',                               [App\Http\Controllers\LecturerTopicController::class, 'destroy'])->name('lecturer.topics.destroy');
    Route::post('/lecturer/topics/{topicId}/participate',                   [App\Http\Controllers\LecturerTopicController::class, 'participate'])->name('lecturer.topics.participate');
    Route::post('/lecturer/posts/{postId}/answer',                          [App\Http\Controllers\LecturerTopicController::class, 'answer'])->name('lecturer.topics.answer');
    Route::post('/lecturer/topics/{topic}/lock',                            [App\Http\Controllers\LecturerTopicController::class, 'lockTopic'])->name('lecturer.topics.lock');
    Route::post('/lecturer/topics/{topic}/pin',                             [App\Http\Controllers\LecturerTopicController::class, 'pinTopic'])->name('lecturer.topics.pin');
    Route::delete('/lecturer/topics/{topic}/users/{userId}',                [App\Http\Controllers\LecturerTopicController::class, 'removeUser'])->name('lecturer.topics.removeUser');
    Route::post('/lecturer/topics/{topic}/users/{userId}/block',            [App\Http\Controllers\LecturerTopicController::class, 'blockUser'])->name('lecturer.topics.blockUser');
    Route::post('/lecturer/topics/{topic}/users/{userId}/unblock',          [App\Http\Controllers\LecturerTopicController::class, 'unblockUser'])->name('lecturer.topics.unblockUser');
});

// ── Administrator Routes ───────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\AdministratorMiddleware::class])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/admin/warnings',                [ModerationController::class, 'warnings'])->name('admin.warnings.index');
    Route::post('/admin/warnings',               [ModerationController::class, 'issueWarning'])->name('admin.warnings.store');
    Route::patch('/admin/warnings/{id}/resolve', [ModerationController::class, 'resolveWarning'])->name('admin.warnings.resolve');
    Route::delete('/admin/warnings/{id}',        [ModerationController::class, 'destroyWarning'])->name('admin.warnings.destroy');

    Route::get('/admin/blacklists',              [ModerationController::class, 'blacklists'])->name('admin.blacklists.index');
    Route::post('/admin/blacklists',             [ModerationController::class, 'blacklistUser'])->name('admin.blacklists.store');
    Route::delete('/admin/blacklists/{id}',      [ModerationController::class, 'destroyBlacklist'])->name('admin.blacklists.destroy');
});

// ── Public Routes ──────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Test Routes (Development Only - Remove in Production) ──────────
Route::middleware('auth')->prefix('test')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\TestAuthController::class, 'dashboard']);
    Route::get('/register',  [\App\Http\Controllers\TestAuthController::class, 'testRegister']);
    Route::get('/session',   [\App\Http\Controllers\TestAuthController::class, 'testSession']);
    Route::get('/roles',     [\App\Http\Controllers\TestAuthController::class, 'testRoles']);
});

Route::middleware(['auth', App\Http\Middleware\MemberMiddleware::class])->group(function () {
    Route::get('/test/member-only', [\App\Http\Controllers\TestAuthController::class, 'memberOnly']);
});

Route::middleware(['auth', App\Http\Middleware\LecturerMiddleware::class])->group(function () {
    Route::get('/test/lecturer-only', [\App\Http\Controllers\TestAuthController::class, 'lecturerOnly']);
});

Route::middleware(['auth', App\Http\Middleware\AdministratorMiddleware::class])->group(function () {
    Route::get('/test/admin-only', [\App\Http\Controllers\TestAuthController::class, 'adminOnly']);
});
