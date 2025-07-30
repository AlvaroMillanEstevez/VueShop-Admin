<?php
// Archivo: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Dashboard routes - todas bajo el prefijo dashboard
Route::prefix('dashboard')->group(function () {
    // Dashboard stats
    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
    Route::get('/top-products', [DashboardController::class, 'topProducts']);
    Route::get('/recent-orders', [DashboardController::class, 'recentOrders']);
    
    // Test endpoint
    Route::get('/test', function () {
        return response()->json([
            'message' => 'VueShop Admin API is working!',
            'timestamp' => now(),
        ]);
    });
    
    // Products - CRUD completo
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    
    // Orders - CRUD completo
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);
    
    // Customers - CRUD completo
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
});