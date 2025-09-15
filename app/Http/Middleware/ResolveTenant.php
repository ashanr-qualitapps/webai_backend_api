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
        
        // Method 1: Resolve by full domain (for different frontend domains)
        $host = $request->getHost();
        $tenant = Tenant::where('domain', $host)->first();
        
        // Method 2: Resolve by subdomain (for subdomain-based tenants)
        if (!$tenant) {
            $subdomain = explode('.', $host)[0];
            $tenant = Tenant::where('domain', $subdomain)->first();
        }
        
        // Method 3: Resolve by Origin header (for CORS requests)
        if (!$tenant && $request->hasHeader('Origin')) {
            $origin = parse_url($request->header('Origin'), PHP_URL_HOST);
            $tenant = Tenant::where('domain', $origin)->first();
        }
        
        // Method 4: Resolve by custom header (fallback for API clients)
        if (!$tenant && $request->hasHeader('X-Tenant-Domain')) {
            $tenant = Tenant::where('domain', $request->header('X-Tenant-Domain'))->first();
        }
        
        // Method 5: Resolve by API key or token payload (for authenticated requests)
        if (!$tenant && $request->bearerToken()) {
            $tenant = $this->resolveTenantFromToken($request);
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
            if ($user instanceof \App\Models\User) {
                return $user->tenant;
            }
        }
        
        return null;
    }
}
