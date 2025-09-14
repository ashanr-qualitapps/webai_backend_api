<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Check if user is authenticated
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $user = Auth::guard('api')->user();

        // Check if user is active
        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Account is inactive'
            ], 403);
        }

        // Check if user has the required permission
        if (!$this->hasPermission($user, $permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user has the required permission
     *
     * @param  mixed  $user
     * @param  string  $permission
     * @return bool
     */
    private function hasPermission($user, string $permission): bool
    {
        // If no permissions are set, deny access
        if (!$user->permissions || empty($user->permissions)) {
            return false;
        }

        $permissions = is_array($user->permissions) ? $user->permissions : json_decode($user->permissions, true);

        // If permissions is not a valid array, deny access
        if (!is_array($permissions)) {
            return false;
        }

        // Check for wildcard permission (super admin)
        if (in_array('*', $permissions) || in_array('admin.*', $permissions)) {
            return true;
        }

        // Check for exact permission match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check for wildcard permissions (e.g., 'users.*' matches 'users.create', 'users.read', etc.)
        foreach ($permissions as $userPermission) {
            if (str_ends_with($userPermission, '.*')) {
                $prefix = substr($userPermission, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get user permissions for debugging
     *
     * @param  mixed  $user
     * @return array
     */
    public static function getUserPermissions($user): array
    {
        if (!$user->permissions || empty($user->permissions)) {
            return [];
        }

        $permissions = is_array($user->permissions) ? $user->permissions : json_decode($user->permissions, true);

        return is_array($permissions) ? $permissions : [];
    }
}
