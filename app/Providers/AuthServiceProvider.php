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
    }
}
