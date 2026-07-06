<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Quiz\QuizController;

// ── Guest Routes ───────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Register
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// ── Authenticated Routes ───────────────────────────────────────────
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Dashboard (All authenticated users)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// ── Member Routes ──────────────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\MemberMiddleware::class])->group(function () {
    // Student quiz routes (SDD §4.2 — Student quiz screen Fig 6.6)
    Route::get('/quizzes',                  [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}',           [QuizController::class, 'take'])->name('quizzes.take');
    Route::post('/quizzes/{quiz}/submit',   [QuizController::class, 'submit'])->name('quizzes.submit');
    Route::get('/quizzes/{quiz}/result',    [QuizController::class, 'result'])->name('quizzes.result');
});

// ── Lecturer Routes ────────────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\LecturerMiddleware::class])->group(function () {
    Route::get('/lecturer/dashboard', function () {
        return view('lecturer.dashboard');
    })->name('lecturer.dashboard');

    // Lecturer quiz management routes (SDD §4.2 — Lecturer quiz screen Fig 6.4)
    Route::get('/lecturer/quizzes/create',          [QuizController::class, 'create'])->name('lecturer.quizzes.create');
    Route::post('/lecturer/quizzes',                [QuizController::class, 'store'])->name('lecturer.quizzes.store');
    Route::get('/lecturer/quizzes/{quiz}',          [QuizController::class, 'show'])->name('lecturer.quizzes.show');
    Route::post('/lecturer/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('lecturer.quizzes.publish');
    Route::post('/lecturer/quizzes/{quiz}/remind',  [QuizController::class, 'remind'])->name('lecturer.quizzes.remind');
    Route::get('/lecturer/quizzes/{quiz}/results',  [QuizController::class, 'results'])->name('lecturer.quizzes.results');
});

// ── Administrator Routes ───────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\AdministratorMiddleware::class])->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');
});

// ── Public Routes ──────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// ── Test Routes (Development Only - Remove in Production) ──────────
Route::middleware('auth')->prefix('test')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\TestAuthController::class, 'dashboard']);
    Route::get('/register', [\App\Http\Controllers\TestAuthController::class, 'testRegister']);
    Route::get('/session', [\App\Http\Controllers\TestAuthController::class, 'testSession']);
    Route::get('/roles', [\App\Http\Controllers\TestAuthController::class, 'testRoles']);
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
