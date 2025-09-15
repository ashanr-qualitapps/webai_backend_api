<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        // Example: resolve tenant by subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];
        $tenant = Tenant::where('domain', $subdomain)->first();

        // Fallback: resolve by header
        if (!$tenant && $request->hasHeader('X-Tenant-Domain')) {
            $tenant = Tenant::where('domain', $request->header('X-Tenant-Domain'))->first();
        }

        if ($tenant) {
            app()->instance('currentTenant', $tenant);
        } else {
            // Optionally, throw or set a default tenant
            // abort(404, 'Tenant not found');
        }

        return $next($request);
    }
}
