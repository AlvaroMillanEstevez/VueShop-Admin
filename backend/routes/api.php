<?php
// Archivo: routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\AuthController;

// Rutas públicas de autenticación
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
});

// Rutas protegidas con autenticación
Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Dashboard routes - todas protegidas
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
                'user' => auth()->user(),
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

    // Admin only routes
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AuthController::class, 'getAllUsers']);
        Route::put('/users/{user}/toggle-status', [AuthController::class, 'toggleUserStatus']);
    });
});