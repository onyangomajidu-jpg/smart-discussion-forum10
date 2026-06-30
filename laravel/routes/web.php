<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\PasswordResetController;

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
    // Member-specific routes will go here
});

// ── Lecturer Routes ────────────────────────────────────────────────
Route::middleware(['auth', App\Http\Middleware\LecturerMiddleware::class])->group(function () {
    Route::get('/lecturer/dashboard', function () {
        return view('lecturer.dashboard');
    })->name('lecturer.dashboard');
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
