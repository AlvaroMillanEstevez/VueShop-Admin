<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Dashboard routes
Route::prefix('dashboard')->group(function () {
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/recent-orders', [DashboardController::class, 'recentOrders']);
});

// Resource routes
Route::apiResource('products', ProductController::class);
Route::apiResource('orders', OrderController::class)->only(['index', 'show', 'update']);
Route::apiResource('customers', CustomerController::class)->only(['index', 'show']);

// Test route
Route::get('/test', function () {
    return response()->json([
        'message' => 'VueShop Admin API is working!',
        'timestamp' => now(),
    ]);
});