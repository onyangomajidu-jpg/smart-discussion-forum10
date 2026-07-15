<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;
use App\Http\Controllers\ExportController;

// Connectivity probe — no auth required
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

// Auth endpoints for Java GUI (Sanctum token-based, no CSRF)
Route::post('/login',  [LoginController::class, 'apiLogin']);
Route::post('/logout', [LoginController::class, 'apiLogout'])->middleware('auth:sanctum');

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
