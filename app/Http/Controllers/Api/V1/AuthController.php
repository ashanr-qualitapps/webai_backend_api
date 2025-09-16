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
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Login user and return access token and refresh token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Post(
        path: "/login",
        summary: "Admin user login",
        description: "Authenticate admin user and return access token",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@webai.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Successful login",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Login successful"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "access_token", type: "string"),
                                new OA\Property(property: "refresh_token", type: "string"),
                                new OA\Property(property: "token_type", type: "string", example: "Bearer"),
                                new OA\Property(property: "expires_in", type: "integer", example: 900),
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "string"),
                                        new OA\Property(property: "email", type: "string"),
                                        new OA\Property(property: "full_name", type: "string"),
                                        new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string")),
                                        new OA\Property(property: "is_active", type: "boolean"),
                                        new OA\Property(property: "last_login", type: "string", format: "date-time")
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Invalid credentials",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Invalid credentials")
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation error"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            )
        ]
    )]
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

        $tenant = app()->has('currentTenant') ? app('currentTenant') : null;
        $user = AdminUser::where('email', $request->email)->first();
        if ($user && $tenant && !$user->tenants->contains($tenant->id)) {
            $user = null;
        }

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

        // Create token with scopes based on user permissions
        $scopes = $this->mapPermissionsToScopes($user->permissions);
        $token = $user->createToken('API Token', $scopes);
        
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
    #[OA\Post(
        path: "/register",
        summary: "Register admin user",
        description: "Create a new admin user for the current tenant",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email", "password", "password_confirmation", "full_name"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123"),
                    new OA\Property(property: "full_name", type: "string", example: "John Doe"),
                    new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["users.read", "users.create"]),
                    new OA\Property(property: "metadata", type: "object", example: ["department" => "IT"]),
                    new OA\Property(property: "is_active", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Admin user created successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Admin user registered successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(
                                    property: "user",
                                    type: "object",
                                    properties: [
                                        new OA\Property(property: "id", type: "string"),
                                        new OA\Property(property: "email", type: "string"),
                                        new OA\Property(property: "full_name", type: "string"),
                                        new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string")),
                                        new OA\Property(property: "is_active", type: "boolean"),
                                        new OA\Property(property: "created_at", type: "string", format: "date-time"),
                                        new OA\Property(property: "updated_at", type: "string", format: "date-time")
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: "Validation error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Validation error"),
                        new OA\Property(property: "errors", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: false),
                        new OA\Property(property: "message", type: "string", example: "Failed to register admin user"),
                        new OA\Property(property: "error", type: "string")
                    ]
                )
            )
        ]
    )]
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
            $tenant = app()->has('currentTenant') ? app('currentTenant') : null;
            $adminUser = AdminUser::create([
                'email' => $request->email,
                'password_hash' => Hash::make($request->password),
                'full_name' => $request->full_name,
                'permissions' => $request->permissions ?? [],
                'metadata' => $request->metadata ?? [],
                'is_active' => $request->is_active ?? true,
                'last_updated' => now()
            ]);
            if ($tenant) {
                $adminUser->tenants()->attach($tenant->id);
            }

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

            // Create new token with same scopes as before
            $scopes = $this->mapPermissionsToScopes($user->permissions);
            $newToken = $user->createToken('API Token', $scopes);

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

    /**
     * Map JSON permissions to OAuth2 scopes
     *
     * @param mixed $permissions
     * @return array
     */
    private function mapPermissionsToScopes($permissions): array
    {
        if (!$permissions || empty($permissions)) {
            return ['read']; // Default scope
        }

        $permissions = is_array($permissions) ? $permissions : json_decode($permissions, true);
        if (!is_array($permissions)) {
            return ['read']; // Default scope
        }

        $scopes = ['read']; // Always include basic read access

        // Map super admin permissions
        if (in_array('*', $permissions) || in_array('admin.*', $permissions)) {
            return [
                'read', 'write', 'delete',
                'users:read', 'users:write', 'users:delete',
                'personas:read', 'personas:write', 'personas:delete',
                'chat:read', 'chat:write', 'chat:delete',
                'knowledge:read', 'knowledge:write', 'knowledge:delete',
                'snippets:read', 'snippets:write', 'snippets:delete',
                'tenants:read', 'tenants:write', 'tenants:delete',
                'super-admin'
            ];
        }

        // Map specific permissions to scopes
        foreach ($permissions as $permission) {
            switch ($permission) {
                // User permissions
                case 'users.read':
                case 'users.*':
                    $scopes[] = 'users:read';
                    if ($permission === 'users.*') {
                        $scopes = array_merge($scopes, ['users:write', 'users:delete']);
                    }
                    break;
                case 'users.create':
                case 'users.update':
                case 'users.write':
                    $scopes[] = 'users:write';
                    $scopes[] = 'write';
                    break;
                case 'users.delete':
                    $scopes[] = 'users:delete';
                    $scopes[] = 'delete';
                    break;

                // Persona permissions
                case 'personas.read':
                case 'personas.*':
                    $scopes[] = 'personas:read';
                    if ($permission === 'personas.*') {
                        $scopes = array_merge($scopes, ['personas:write', 'personas:delete']);
                    }
                    break;
                case 'personas.create':
                case 'personas.update':
                case 'personas.write':
                    $scopes[] = 'personas:write';
                    $scopes[] = 'write';
                    break;
                case 'personas.delete':
                    $scopes[] = 'personas:delete';
                    $scopes[] = 'delete';
                    break;

                // Chat permissions
                case 'chat.read':
                case 'chat.*':
                    $scopes[] = 'chat:read';
                    if ($permission === 'chat.*') {
                        $scopes = array_merge($scopes, ['chat:write', 'chat:delete']);
                    }
                    break;
                case 'chat.create':
                case 'chat.update':
                case 'chat.write':
                    $scopes[] = 'chat:write';
                    $scopes[] = 'write';
                    break;
                case 'chat.delete':
                    $scopes[] = 'chat:delete';
                    $scopes[] = 'delete';
                    break;

                // Knowledge base permissions
                case 'knowledge.read':
                case 'knowledge.*':
                    $scopes[] = 'knowledge:read';
                    if ($permission === 'knowledge.*') {
                        $scopes = array_merge($scopes, ['knowledge:write', 'knowledge:delete']);
                    }
                    break;
                case 'knowledge.create':
                case 'knowledge.update':
                case 'knowledge.write':
                    $scopes[] = 'knowledge:write';
                    $scopes[] = 'write';
                    break;
                case 'knowledge.delete':
                    $scopes[] = 'knowledge:delete';
                    $scopes[] = 'delete';
                    break;

                // Snippet permissions
                case 'snippets.read':
                case 'snippets.*':
                    $scopes[] = 'snippets:read';
                    if ($permission === 'snippets.*') {
                        $scopes = array_merge($scopes, ['snippets:write', 'snippets:delete']);
                    }
                    break;
                case 'snippets.create':
                case 'snippets.update':
                case 'snippets.write':
                    $scopes[] = 'snippets:write';
                    $scopes[] = 'write';
                    break;
                case 'snippets.delete':
                    $scopes[] = 'snippets:delete';
                    $scopes[] = 'delete';
                    break;

                // Tenant permissions
                case 'tenants.read':
                case 'tenants.*':
                    $scopes[] = 'tenants:read';
                    if ($permission === 'tenants.*') {
                        $scopes = array_merge($scopes, ['tenants:write', 'tenants:delete']);
                    }
                    break;
                case 'tenants.create':
                case 'tenants.update':
                case 'tenants.write':
                    $scopes[] = 'tenants:write';
                    $scopes[] = 'write';
                    break;
                case 'tenants.delete':
                    $scopes[] = 'tenants:delete';
                    $scopes[] = 'delete';
                    break;

                // Admin permissions
                case 'admin':
                case 'admin.read':
                    $scopes[] = 'admin';
                    break;
            }
        }

        return array_unique($scopes);
    }
}
