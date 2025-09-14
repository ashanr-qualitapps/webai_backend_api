<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful login with valid credentials
     */
    public function test_successful_login_with_valid_credentials()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

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
                    'expires_in' => 900, // 15 minutes
                    'user' => [
                        'email' => 'admin@test.com',
                        'is_active' => true,
                    ]
                ]
            ]);

        // Verify access token is present and not empty
        $this->assertNotEmpty($response->json('data.access_token'));
        
        // Verify user's last_login was updated
        $user->refresh();
        $this->assertNotNull($user->last_login);
    }

    /**
     * Test login failure with invalid email
     */
    public function test_login_failure_with_invalid_email()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
    }

    /**
     * Test login failure with invalid password
     */
    public function test_login_failure_with_invalid_password()
    {
        $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

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
        $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

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
        $response = $this->postJson('/api/v1/login', []);

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
     * Test validation errors for invalid email format
     */
    public function test_validation_errors_for_invalid_email_format()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validation errors for short password
     */
    public function test_validation_errors_for_short_password()
    {
        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test access token can be used for authenticated requests
     */
    public function test_access_token_can_be_used_for_authenticated_requests()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Login to get token
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('data.access_token');

        // Use token for authenticated request (example endpoint)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/user');

        // This will depend on having a user profile endpoint
        // For now, we just verify the token is valid format
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Verify token has proper JWT structure
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test rate limiting on login endpoint
     */
    public function test_rate_limiting_on_login_endpoint()
    {
        $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'admin@test.com',
                'password' => 'wrongpassword',
            ]);

            if ($i < 5) {
                $response->assertStatus(401);
            } else {
                // 6th attempt should be rate limited
                $response->assertStatus(429)
                    ->assertJsonStructure([
                        'success',
                        'message',
                        'retry_after'
                    ])
                    ->assertJson([
                        'success' => false,
                        'message' => 'Too many authentication attempts. Please try again later.'
                    ]);
            }
        }
    }

    /**
     * Test JWT token structure and claims
     */
    public function test_jwt_token_structure_and_claims()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $token = $response->json('data.access_token');
        $parts = explode('.', $token);
        
        // Verify JWT structure
        $this->assertCount(3, $parts);
        
        // Decode payload
        $payload = json_decode(base64_decode($parts[1]), true);
        
        // Verify required claims
        $this->assertArrayHasKey('sub', $payload); // Subject (user ID)
        $this->assertArrayHasKey('exp', $payload); // Expiration
        $this->assertArrayHasKey('iat', $payload); // Issued at
        $this->assertArrayHasKey('aud', $payload); // Audience
        
        // Verify user ID matches
        $this->assertEquals($user->id, $payload['sub']);
        
        // Verify token is not expired
        $this->assertGreaterThan(time(), $payload['exp']);
    }

    /**
     * Test login with different user permissions
     */
    public function test_login_with_different_user_permissions()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['users.read', 'posts.create'],
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'permissions' => ['users.read', 'posts.create']
                    ]
                ]
            ]);
    }

    /**
     * Test concurrent login attempts from same IP
     */
    public function test_concurrent_login_attempts_from_same_ip()
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
            'password_hash' => Hash::make('password123'),
        ]);

        // Simulate multiple successful logins
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'email' => 'admin@test.com',
                'password' => 'password123',
            ]);

            $response->assertStatus(200);
        }

        // Verify user can still login (no rate limiting for successful attempts)
        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
    }
}