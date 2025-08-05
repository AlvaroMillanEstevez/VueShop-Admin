<?php
// Archivo: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RegisterController;

// Public routes for auth
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/register', [RegisterController::class, 'register']);
});

// Protected routes with auth
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Dashboard routes - all protected
    Route::prefix('dashboard')->group(function () {
        // Dashboard stats
        Route::get('/stats', [DashboardController::class, 'stats']);
        Route::get('/sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/recent-orders', [DashboardController::class, 'recentOrders']);

        // Products - full CRUD
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{product}', [ProductController::class, 'show']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Orders - full CRUD 
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

        Route::post('/orders/recalculate-totals', [OrderController::class, 'recalculateTotals']);

        // Customers - full CRUD 
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::put('/customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
        Route::get('/customers-stats', [CustomerController::class, 'stats']);
    });

    // Admin only routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getAllUsers']);
        Route::put('/users/{user}/toggle-status', [AuthController::class, 'toggleUserStatus']);
    });
});
