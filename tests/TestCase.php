<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Models\AdminUser;
use App\Models\Tenant;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup for tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Database migrations should already be run in the test environment
        
        // Setup Passport for testing
        $this->setupPassport();
    }
    
    /**
     * Setup Passport for testing
     */
    protected function setupPassport(): void
    {
        // Use Passport testing helpers to create keys and client
        \Laravel\Passport\Passport::loadKeysFrom(storage_path());
        
        // Create client record directly with proper array format for grant_types
        try {
            DB::table('oauth_clients')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'owner_type' => null,
                'owner_id' => null,
                'name' => 'Test Personal Access Client',
                'secret' => \Illuminate\Support\Str::random(40),
                'provider' => 'admin_users',
                'redirect_uris' => '[]',
                'grant_types' => '["personal_access"]',
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Client might already exist, continue
        }
    }

    /**
     * Create a test tenant
     */
    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::factory()->create(array_merge([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'app_key' => \Illuminate\Support\Str::uuid(),
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create a test admin user
     */
    protected function createAdminUser(array $attributes = []): AdminUser
    {
        return AdminUser::factory()->create(array_merge([
            'email' => 'test@example.com',
            'password_hash' => bcrypt('password123'),
            'full_name' => 'Test Admin',
            'permissions' => ['admin.*'],
            'is_active' => true,
        ], $attributes));
    }

    /**
     * Create and authenticate an admin user
     */
    protected function actingAsAdmin(array $attributes = []): AdminUser
    {
        $user = $this->createAdminUser($attributes);
        $this->actingAs($user, 'api');
        return $user;
    }

    /**
     * Create a test admin user with specific scopes for token testing
     */
    protected function createAdminUserWithToken(array $userAttributes = [], array $scopes = ['read']): array
    {
        $user = $this->createAdminUser($userAttributes);
        $token = $user->createToken('Test Token', $scopes);
        
        return [
            'user' => $user,
            'token' => $token->accessToken,
            'scopes' => $scopes
        ];
    }

    /**
     * Set the current tenant for testing
     */
    protected function setCurrentTenant(Tenant $tenant): void
    {
        app()->instance('currentTenant', $tenant);
    }

    /**
     * Create a request with tenant headers
     */
    protected function withTenantHeaders(string $appKey): array
    {
        return [
            'X-Tenant-Key' => $appKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Create a request with authorization and tenant headers
     */
    protected function withAuthAndTenantHeaders(string $token, string $appKey): array
    {
        return array_merge($this->withTenantHeaders($appKey), [
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    /**
     * Assert that a response contains OAuth2 scopes
     */
    protected function assertHasScopes(array $expectedScopes, array $actualScopes): void
    {
        foreach ($expectedScopes as $scope) {
            $this->assertContains($scope, $actualScopes, "Expected scope '{$scope}' not found in token scopes");
        }
    }

    /**
     * Get the scope mapping for a given set of permissions
     */
    protected function getScopesForPermissions(array $permissions): array
    {
        // Replicate the mapping logic from AuthController
        if (in_array('*', $permissions) || in_array('admin.*', $permissions)) {
            return [
                'read', 'write', 'delete',
                'users:read', 'users:write', 'users:delete',
                'personas:read', 'personas:write', 'personas:delete',
                'chat:read', 'chat:write', 'chat:delete',
                'knowledge:read', 'knowledge:write', 'knowledge:delete',
                'snippets:read', 'snippets:write', 'snippets:delete',
                'suggestions:read', 'suggestions:write', 'suggestions:delete',
                'tenants:read', 'tenants:write', 'tenants:delete',
                'super-admin'
            ];
        }

        $scopes = ['read']; // Always include basic read access
        
        foreach ($permissions as $permission) {
            // Map specific permissions to scopes
            if (str_contains($permission, 'users')) {
                if (str_contains($permission, 'read') || str_contains($permission, '*')) {
                    $scopes[] = 'users:read';
                }
                if (str_contains($permission, 'create') || str_contains($permission, 'update') || str_contains($permission, 'write') || str_contains($permission, '*')) {
                    $scopes[] = 'users:write';
                    $scopes[] = 'write';
                }
                if (str_contains($permission, 'delete') || str_contains($permission, '*')) {
                    $scopes[] = 'users:delete';
                    $scopes[] = 'delete';
                }
            }
            // Add similar logic for other modules as needed
        }
        
        return array_unique($scopes);
    }
}
