<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Carbon\Carbon;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Configure Passport token lifetimes
        Passport::tokensExpireIn(Carbon::now()->addMinutes(15)); // Short-lived access tokens (15 minutes)
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30)); // Refresh tokens last 30 days
        Passport::personalAccessTokensExpireIn(Carbon::now()->addMonths(6)); // Personal access tokens last 6 months

        // Define Passport OAuth2 scopes
        Passport::tokensCan([
            // Basic access scopes
            'read' => 'Read basic data',
            'write' => 'Create and update data',
            'delete' => 'Delete data',
            
            // Resource-specific scopes
            'users:read' => 'Read user information',
            'users:write' => 'Create and update users',
            'users:delete' => 'Delete users',
            
            'personas:read' => 'Read personas',
            'personas:write' => 'Create and update personas',
            'personas:delete' => 'Delete personas',
            
            'chat:read' => 'Read chat sessions and messages',
            'chat:write' => 'Create chat sessions and messages',
            'chat:delete' => 'Delete chat sessions and messages',
            
            'knowledge:read' => 'Read knowledge base',
            'knowledge:write' => 'Create and update knowledge base',
            'knowledge:delete' => 'Delete knowledge base entries',
            
            'snippets:read' => 'Read snippets',
            'snippets:write' => 'Create and update snippets',
            'snippets:delete' => 'Delete snippets',
            
            'tenants:read' => 'Read tenant information',
            'tenants:write' => 'Create and update tenants',
            'tenants:delete' => 'Delete tenants',
            
            // Administrative scopes
            'admin' => 'Full administrative access',
            'super-admin' => 'Super administrative access',
        ]);

        // Set default scopes that will be assigned if no specific scopes are requested
        Passport::setDefaultScope([
            'read'
        ]);
    }
}
