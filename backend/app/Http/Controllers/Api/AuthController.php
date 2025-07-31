<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Log básico
        Log::info('=== LOGIN REQUEST START ===');
        Log::info('Request method: ' . $request->method());
        Log::info('Request URL: ' . $request->url());
        Log::info('Request data: ', $request->all());
        
        try {
            // Verificar que lleguen los datos
            if (!$request->email || !$request->password) {
                Log::error('Missing email or password');
                return response()->json([
                    'error' => 'Email and password are required'
                ], 400);
            }

            Log::info('Searching user with email: ' . $request->email);
            
            // Buscar usuario (con timeout control)
            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                Log::error('User not found: ' . $request->email);
                return response()->json([
                    'error' => 'User not found'
                ], 404);
            }

            Log::info('User found: ' . $user->id . ' - ' . $user->name);

            // Verificar contraseña
            if (!Hash::check($request->password, $user->password)) {
                Log::error('Invalid password for: ' . $request->email);
                return response()->json([
                    'error' => 'Invalid password'
                ], 401);
            }

            Log::info('Password verified, checking if user is active');

            if (!$user->is_active) {
                Log::error('User is inactive: ' . $request->email);
                return response()->json([
                    'error' => 'Account is deactivated'
                ], 401);
            }

            Log::info('Creating JWT token...');

            // Intentar crear token JWT
            try {
                $token = JWTAuth::fromUser($user);
                Log::info('JWT token created successfully');
            } catch (\Exception $e) {
                Log::error('JWT Error: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Could not create token: ' . $e->getMessage()
                ], 500);
            }

            Log::info('Login successful for user: ' . $user->id);

            $response = [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ];

            Log::info('=== LOGIN REQUEST END (SUCCESS) ===');
            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('=== LOGIN REQUEST END (ERROR) ===');
            Log::error('Login exception: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Internal server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function register(Request $request)
    {
        Log::info('=== REGISTER REQUEST ===');
        
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'manager',
                'is_active' => true,
            ]);

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60,
                'user' => $user
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Logout failed'], 500);
        }
    }

    public function profile(Request $request)
    {
        return response()->json(['user' => Auth::user()]);
    }

    public function refresh(Request $request)
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return response()->json([
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => JWTAuth::factory()->getTTL() * 60
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token refresh failed'], 401);
        }
    }

    /**
     * Get all users (Admin only)
     */
    public function getAllUsers()
    {
        try {
            $users = User::withCount(['orders', 'products', 'customers'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'is_active' => $user->is_active,
                        'orders_count' => $user->orders_count,
                        'products_count' => $user->products_count,
                        'customers_count' => $user->customers_count,
                        'created_at' => $user->created_at,
                    ];
                });

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error getting users: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading users'], 500);
        }
    }

    /**
     * Toggle user status (Admin only)
     */
    public function toggleUserStatus(User $user)
    {
        try {
            $user->update(['is_active' => !$user->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling user status: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating user status'], 500);
        }
    }
}