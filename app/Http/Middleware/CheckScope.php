<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Http\Middleware\CheckScopes;
use Symfony\Component\HttpFoundation\Response;

class CheckScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$scopes
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$scopes): Response
    {
        // Check if user is authenticated
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $token = $request->user()->token();

        // Check if token exists
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        // Check if token has any of the required scopes
        if (!$this->hasAnyScope($token, $scopes)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient scope',
                'required_scopes' => $scopes,
                'token_scopes' => $token->scopes
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if token has any of the required scopes
     *
     * @param  mixed  $token
     * @param  array  $scopes
     * @return bool
     */
    private function hasAnyScope($token, array $scopes): bool
    {
        if (empty($scopes)) {
            return true;
        }

        $tokenScopes = $token->scopes ?? [];

        // Check for super-admin scope
        if (in_array('super-admin', $tokenScopes)) {
            return true;
        }

        // Check for admin scope (grants access to most things)
        if (in_array('admin', $tokenScopes) && !in_array('super-admin', $scopes)) {
            return true;
        }

        // Check if token has any of the required scopes
        foreach ($scopes as $scope) {
            if (in_array($scope, $tokenScopes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if token has all of the required scopes
     *
     * @param  mixed  $token
     * @param  array  $scopes
     * @return bool
     */
    private function hasAllScopes($token, array $scopes): bool
    {
        if (empty($scopes)) {
            return true;
        }

        $tokenScopes = $token->scopes ?? [];

        // Check for super-admin scope
        if (in_array('super-admin', $tokenScopes)) {
            return true;
        }

        // Check if token has all required scopes
        foreach ($scopes as $scope) {
            if (!in_array($scope, $tokenScopes)) {
                return false;
            }
        }

        return true;
    }
}