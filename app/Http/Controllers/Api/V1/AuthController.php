<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    /**
     * Login user and return access token and refresh token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = AdminUser::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive'
            ], 403);
        }

        // Create token with scopes
        $token = $user->createToken('API Token');
        
        // Update last login
        $user->update([
            'last_login' => now(),
            'last_updated' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'access_token' => $token->accessToken,
                'refresh_token' => $token->token->refresh_token ? $token->token->refresh_token->id : null,
                'token_type' => 'Bearer',
                'expires_in' => 15 * 60, // 15 minutes in seconds
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'full_name' => $user->full_name,
                    'permissions' => $user->permissions,
                    'is_active' => $user->is_active,
                    'last_login' => $user->last_login
                ]
            ]
        ], 200);
    }

    /**
     * Register a new admin user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admin_users,email',
            'password' => 'required|string|min:8|confirmed',
            'full_name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create new admin user
            $adminUser = AdminUser::create([
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'permissions' => $request->permissions ?? [],
                'metadata' => $request->metadata ?? [],
                'is_active' => $request->is_active ?? true,
                'last_updated' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Admin user registered successfully',
                'data' => [
                    'user' => [
                        'id' => $adminUser->id,
                        'email' => $adminUser->email,
                        'full_name' => $adminUser->full_name,
                        'permissions' => $adminUser->permissions,
                        'is_active' => $adminUser->is_active,
                        'created_at' => $adminUser->created_at,
                        'updated_at' => $adminUser->updated_at
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register admin user',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Refresh access token using refresh token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Find the refresh token
            $refreshToken = RefreshToken::where('id', $request->refresh_token)
                ->where('revoked', false)
                ->first();

            if (!$refreshToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid refresh token'
                ], 401);
            }

            // Check if refresh token is expired
            if ($refreshToken->expires_at && $refreshToken->expires_at->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Refresh token expired'
                ], 401);
            }

            // Get the access token
            $accessToken = Token::find($refreshToken->access_token_id);
            if (!$accessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid refresh token'
                ], 401);
            }

            // Get the user
            $user = AdminUser::find($accessToken->user_id);
            if (!$user || !$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found or inactive'
                ], 401);
            }

            // Revoke old tokens
            $accessToken->revoke();
            $refreshToken->revoke();

            // Create new token
            $newToken = $user->createToken('API Token');

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'access_token' => $newToken->accessToken,
                    'refresh_token' => $newToken->token->refresh_token ? $newToken->token->refresh_token->id : null,
                    'token_type' => 'Bearer',
                    'expires_in' => 15 * 60, // 15 minutes in seconds
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'full_name' => $user->full_name,
                        'permissions' => $user->permissions,
                        'is_active' => $user->is_active,
                        'last_login' => $user->last_login
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout user and revoke token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if ($user) {
                // Revoke all tokens for the user
                $user->tokens->each(function ($token) {
                    $token->revoke();
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get current authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::guard('api')->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'full_name' => $user->full_name,
                        'permissions' => $user->permissions,
                        'is_active' => $user->is_active,
                        'last_login' => $user->last_login,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user information',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
