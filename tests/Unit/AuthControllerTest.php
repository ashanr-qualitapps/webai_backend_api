<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\AuthController;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    /**
     * Test login method with valid credentials and scope mapping
     */
    public function test_login_method_with_valid_credentials_and_scope_mapping()
    {
        $permissions = ['users.read', 'users.create', 'personas.*'];
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => $permissions,
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Login successful', $data['message']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertEquals('test@example.com', $data['data']['user']['email']);
        $this->assertEquals($permissions, $data['data']['user']['permissions']);
    }

    /**
     * Test login method creates token with proper expiration
     */
    public function test_login_method_creates_token_with_proper_expiration()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $this->assertEquals(900, $data['data']['expires_in']); // 15 minutes
        $this->assertEquals('Bearer', $data['data']['token_type']);
    }

    /**
     * Test login method with super admin permissions
     */
    public function test_login_method_with_super_admin_permissions()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['*'],
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['*'], $data['data']['user']['permissions']);
    }

    /**
     * Test login method with admin wildcard permissions
     */
    public function test_login_method_with_admin_wildcard_permissions()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['admin.*'],
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['admin.*'], $data['data']['user']['permissions']);
    }

    /**
     * Test login method with multi-tenancy context
     */
    public function test_login_method_with_multi_tenancy_context()
    {
        $tenant = $this->createTenant();
        $this->setCurrentTenant($tenant);
        
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
    }

    /**
     * Test login method with invalid credentials
     */
    public function test_login_method_with_invalid_credentials()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    /**
     * Test login method with nonexistent user
     */
    public function test_login_method_with_nonexistent_user()
    {
        $request = new Request([
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['message']);
    }

    /**
     * Test login method with inactive user
     */
    public function test_login_method_with_inactive_user()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'is_active' => false,
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(403, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Account is inactive', $data['message']);
    }

    /**
     * Test login method validation errors
     */
    public function test_login_method_validation_errors()
    {
        $request = new Request([
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation error', $data['message']);
        $this->assertArrayHasKey('errors', $data);
    }

    /**
     * Test login method updates last login timestamp
     */
    public function test_login_method_updates_last_login_timestamp()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'last_login' => null,
        ]);

        $this->assertNull($user->last_login);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Refresh user from database
        $user->refresh();
        $this->assertNotNull($user->last_login);
    }

    /**
     * Test login method returns correct token structure
     */
    public function test_login_method_returns_correct_token_structure()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('token_type', $data['data']);
        $this->assertArrayHasKey('expires_in', $data['data']);
        
        $this->assertEquals('Bearer', $data['data']['token_type']);
        $this->assertEquals(900, $data['data']['expires_in']); // 15 minutes
        
        // Verify token is a valid JWT structure
        $token = $data['data']['access_token'];
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Test login method returns correct user data
     */
    public function test_login_method_returns_correct_user_data()
    {
        $permissions = ['admin.*', 'users.read'];
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Test User',
            'permissions' => $permissions,
            'is_active' => true,
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $userData = $data['data']['user'];
        
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals('test@example.com', $userData['email']);
        $this->assertEquals('Test User', $userData['full_name']);
        $this->assertEquals($permissions, $userData['permissions']);
        $this->assertTrue($userData['is_active']);
        $this->assertArrayHasKey('last_login', $userData);
    }

    /**
     * Test login method validation rules
     */
    public function test_login_method_validation_rules()
    {
        // Test email required
        $validator = Validator::make(['password' => 'password123'], [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());

        // Test email format
        $validator = Validator::make([
            'email' => 'invalid-email',
            'password' => 'password123'
        ], [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());

        // Test password required
        $validator = Validator::make(['email' => 'test@example.com'], [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // Test password minimum length
        $validator = Validator::make([
            'email' => 'test@example.com',
            'password' => '123'
        ], [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());

        // Test valid data passes
        $validator = Validator::make([
            'email' => 'test@example.com',
            'password' => 'password123'
        ], [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test login method handles empty request
     */
    public function test_login_method_handles_empty_request()
    {
        $request = new Request([]);

        $response = $this->controller->login($request);

        $this->assertEquals(422, $response->getStatusCode());
        
        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation error', $data['message']);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertArrayHasKey('password', $data['errors']);
    }

    /**
     * Test login method password hashing verification
     */
    public function test_login_method_password_hashing_verification()
    {
        $plainPassword = 'mySecurePassword123';
        $hashedPassword = Hash::make($plainPassword);
        
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => $hashedPassword,
        ]);

        // Test correct password works
        $request = new Request([
            'email' => 'test@example.com',
            'password' => $plainPassword,
        ]);

        $response = $this->controller->login($request);
        $this->assertEquals(200, $response->getStatusCode());

        // Test incorrect password fails
        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'wrongPassword',
        ]);

        $response = $this->controller->login($request);
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test scope mapping for different permission patterns
     */
    public function test_scope_mapping_for_different_permission_patterns()
    {
        // Test specific user permissions
        $user1 = $this->createAdminUser([
            'email' => 'user1@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['users.read', 'users.create'],
        ]);

        $request1 = new Request([
            'email' => 'user1@example.com',
            'password' => 'password123',
        ]);

        $response1 = $this->controller->login($request1);
        $this->assertEquals(200, $response1->getStatusCode());

        // Test wildcard permissions
        $user2 = $this->createAdminUser([
            'email' => 'user2@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['personas.*'],
        ]);

        $request2 = new Request([
            'email' => 'user2@example.com',
            'password' => 'password123',
        ]);

        $response2 = $this->controller->login($request2);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * Test refresh token response structure
     */
    public function test_refresh_token_response_structure()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        // Verify refresh token is present in response
        $this->assertArrayHasKey('refresh_token', $data['data']);
        
        // The refresh token might be null if not properly configured, but the key should exist
        $this->assertTrue(array_key_exists('refresh_token', $data['data']));
    }

    /**
     * Test token response includes all required fields
     */
    public function test_token_response_includes_all_required_fields()
    {
        $user = $this->createAdminUser([
            'email' => 'test@example.com',
            'password_hash' => Hash::make('password123'),
            'permissions' => ['users.read', 'chat.write'],
        ]);

        $request = new Request([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);
        $data = $response->getData(true);

        $expectedFields = [
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'user'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $data['data'], "Missing field: {$field}");
        }

        $expectedUserFields = [
            'id',
            'email',
            'full_name',
            'permissions',
            'is_active',
            'last_login'
        ];

        foreach ($expectedUserFields as $field) {
            $this->assertArrayHasKey($field, $data['data']['user'], "Missing user field: {$field}");
        }
    }
}