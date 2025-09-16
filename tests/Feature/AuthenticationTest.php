<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $testTenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testTenant = $this->createTenant();
        $this->setCurrentTenant($this->testTenant);
    }

    /**
     * Test successful login with valid credentials
     */
    public function test_successful_login_with_valid_credentials()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($this->testTenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'access_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'email',
                        'full_name',
                        'permissions',
                        'is_active',
                        'last_login'
                    ]
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => 900,
                    'user' => [
                        'email' => 'admin@test.com',
                        'is_active' => true,
                    ]
                ]
            ]);

        $this->assertNotEmpty($response->json('data.access_token'));
        
        // Check if refresh token is provided (optional for personal access tokens)
        $refreshToken = $response->json('data.refresh_token');
        if ($refreshToken !== null) {
            $this->assertNotEmpty($refreshToken);
        }
        
        $user->refresh();
        $this->assertNotNull($user->last_login);
    }

    /**
     * Test login with multi-tenancy app key
     */
    public function test_login_with_multi_tenancy_app_key()
    {
        $tenant = $this->createTenant([
            'name' => 'Specific Tenant',
            'domain' => 'specific.example.com'
        ]);
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Associate the user with the specific tenant
        $user->tenants()->attach($tenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($tenant->app_key));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
    }

    /**
     * Test login failure with invalid credentials
     */
    public function test_login_failure_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
    }

    /**
     * Test login failure with inactive user
     */
    public function test_login_failure_with_inactive_user()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
            'is_active' => false,
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($this->testTenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Account is inactive'
            ]);
    }

    /**
     * Test validation errors for missing fields
     */
    public function test_validation_errors_for_missing_fields()
    {
        $response = $this->postJson('/api/v1/login', [], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'email',
                    'password'
                ]
            ])
            ->assertJson([
                'success' => false,
                'message' => 'Validation error'
            ]);
    }

    /**
     * Test OAuth2 scopes in login response
     */
    public function test_oauth2_scopes_in_login_response()
    {
        $permissions = ['users.read', 'users.create', 'personas.*'];
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => $permissions,
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($this->testTenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(200);
        $this->assertEquals($permissions, $response->json('data.user.permissions'));
        $this->assertNotEmpty($response->json('data.access_token'));
    }

    /**
     * Test JWT token structure
     */
    public function test_jwt_token_structure()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($this->testTenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $token = $response->json('data.access_token');
        $parts = explode('.', $token);
        
        $this->assertCount(3, $parts);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    /**
     * Test super admin permissions
     */
    public function test_super_admin_permissions()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['*'],
        ]);

        // Associate the user with the tenant
        $user->tenants()->attach($this->testTenant->id);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ], $this->withTenantHeaders($this->testTenant->app_key));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'permissions' => ['*']
                    ]
                ]
            ]);

        $this->assertNotEmpty($response->json('data.access_token'));
        
        // Check if refresh token is provided (optional for personal access tokens)
        $refreshToken = $response->json('data.refresh_token');
        if ($refreshToken !== null) {
            $this->assertNotEmpty($refreshToken);
        }
    }
}
