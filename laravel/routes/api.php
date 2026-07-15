<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StatisticsApiController;

// Connectivity probe — no auth required
Route::get('/ping', fn() => response()->json(['status' => 'ok']));

// Auth endpoints for Java GUI (Sanctum token-based, no CSRF)
Route::post('/login',  [LoginController::class, 'apiLogin']);
Route::post('/logout', [LoginController::class, 'apiLogout'])->middleware('auth:sanctum');

// Dashboard stats — accepts Bearer token (Java GUI) or web session
Route::middleware('auth:sanctum')->get('/dashboard',  [DashboardApiController::class,  'index']);
Route::middleware('auth:sanctum')->get('/statistics', [StatisticsApiController::class, 'index']);
