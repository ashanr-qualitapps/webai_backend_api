<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = null;
        
        // Method 1: Resolve by app_key header (PRIMARY METHOD)
        if ($request->hasHeader('X-App-Key')) {
            $appKey = $request->header('X-App-Key');
            $tenant = Tenant::findByAppKey($appKey);
        }
        
        // Method 2: Resolve by app_key in Authorization header format (e.g., "Bearer app_key_here")
        if (!$tenant && $request->hasHeader('X-Tenant-Key')) {
            $appKey = $request->header('X-Tenant-Key');
            $tenant = Tenant::findByAppKey($appKey);
        }
        
        // Method 3: Resolve by app_key from authenticated user's tenant relationship
        if (!$tenant && $request->bearerToken()) {
            $tenant = $this->resolveTenantFromToken($request);
        }
        
        // Legacy Methods (for backward compatibility) - DEPRECATED
        // Method 4: Resolve by full domain (for different frontend domains)
        if (!$tenant) {
            $host = $request->getHost();
            $tenant = Tenant::findByDomain($host);
        }
        
        // Method 5: Resolve by subdomain (for subdomain-based tenants)
        if (!$tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('domain', $subdomain)->where('is_active', true)->first();
        }
        
        // Method 6: Resolve by Origin header (for CORS requests)
        if (!$tenant && $request->hasHeader('Origin')) {
            $origin = parse_url($request->header('Origin'), PHP_URL_HOST);
            $tenant = Tenant::findByDomain($origin);
        }
        
        // Method 7: Resolve by custom header (fallback for API clients)
        if (!$tenant && $request->hasHeader('X-Tenant-Domain')) {
            $tenant = Tenant::findByDomain($request->header('X-Tenant-Domain'));
        }

        if ($tenant) {
            app()->instance('currentTenant', $tenant);
        }

        return $next($request);
    }
    
    private function resolveTenantFromToken(Request $request)
    {
        // If user is authenticated, get tenant from user's relationship
        if (auth('api')->check()) {
            $user = auth('api')->user();
            
            // For admin users (many-to-many with tenants)
            if ($user instanceof \App\Models\AdminUser) {
                return $user->tenants()->first(); // Get first tenant or implement logic for multiple
            }
            
            // For regular users (belongs to one tenant)
            // Note: Commented out until User model is created
            // if ($user instanceof \App\Models\User) {
            //     return $user->tenant;
            // }
        }
        
        return null;
    }
}
