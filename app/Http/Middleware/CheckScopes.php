<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckScopes
{
    /**
     * Handle an incoming request.
     * This middleware requires ALL specified scopes (AND logic)
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

        // Check if token has all required scopes
        if (!$this->hasAllScopes($token, $scopes)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient scopes',
                'required_scopes' => $scopes,
                'token_scopes' => $token->scopes,
                'missing_scopes' => array_diff($scopes, $token->scopes ?? [])
            ], 403);
        }

        return $next($request);
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