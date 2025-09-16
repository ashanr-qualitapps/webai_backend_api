<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test AdminUser model can be created with factory
     */
    public function test_admin_user_can_be_created_with_factory()
    {
        $user = AdminUser::factory()->create();

        $this->assertInstanceOf(AdminUser::class, $user);
        $this->assertNotEmpty($user->id);
        $this->assertNotEmpty($user->email);
        $this->assertNotEmpty($user->full_name);
        $this->assertTrue($user->is_active);
    }

    /**
     * Test AdminUser uses UUID for primary key
     */
    public function test_admin_user_uses_uuid_for_primary_key()
    {
        $user = AdminUser::factory()->create();

        $this->assertIsString($user->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $user->id
        );
    }

    /**
     * Test AdminUser has API tokens trait
     */
    public function test_admin_user_has_api_tokens_trait()
    {
        $user = new AdminUser();
        
        $this->assertContains(HasApiTokens::class, class_uses_recursive($user));
    }

    /**
     * Test getAuthIdentifier returns user ID
     */
    public function test_get_auth_identifier_returns_user_id()
    {
        $user = AdminUser::factory()->create();

        $this->assertEquals($user->id, $user->getAuthIdentifier());
    }

    /**
     * Test getAuthIdentifierName returns primary key name
     */
    public function test_get_auth_identifier_name_returns_primary_key_name()
    {
        $user = new AdminUser();

        $this->assertEquals('id', $user->getAuthIdentifierName());
    }

    /**
     * Test getAuthPassword returns password hash
     */
    public function test_get_auth_password_returns_password_hash()
    {
        $passwordHash = Hash::make('password123');
        $user = AdminUser::factory()->create([
            'password_hash' => $passwordHash,
        ]);

        $this->assertEquals($passwordHash, $user->getAuthPassword());
    }

    /**
     * Test AdminUser fillable attributes
     */
    public function test_admin_user_fillable_attributes()
    {
        $user = new AdminUser();
        $expected = [
            'email',
            'password_hash',
            'full_name',
            'permissions',
            'metadata',
            'updated_by',
            'last_login',
            'is_active',
            'last_updated',
        ];

        $this->assertEquals($expected, $user->getFillable());
    }

    /**
     * Test AdminUser hidden attributes
     */
    public function test_admin_user_hidden_attributes()
    {
        $user = new AdminUser();
        $expected = [
            'password_hash',
            'remember_token',
        ];

        $this->assertEquals($expected, $user->getHidden());
    }

    /**
     * Test AdminUser casts attributes correctly
     */
    public function test_admin_user_casts_attributes_correctly()
    {
        $user = AdminUser::factory()->create([
            'permissions' => ['admin.*', 'users.read'],
            'metadata' => ['role' => 'super_admin'],
            'is_active' => true,
        ]);

        $this->assertIsArray($user->permissions);
        $this->assertIsArray($user->metadata);
        $this->assertIsBool($user->is_active);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->last_updated);
    }

    /**
     * Test AdminUser scope for active users
     */
    public function test_admin_user_scope_for_active_users()
    {
        // Create active and inactive users
        AdminUser::factory()->create(['is_active' => true]);
        AdminUser::factory()->create(['is_active' => false]);
        AdminUser::factory()->create(['is_active' => true]);

        $activeUsers = AdminUser::where('is_active', true)->get();
        $inactiveUsers = AdminUser::where('is_active', false)->get();

        $this->assertCount(2, $activeUsers);
        $this->assertCount(1, $inactiveUsers);
    }

    /**
     * Test AdminUser email uniqueness
     */
    public function test_admin_user_email_uniqueness()
    {
        $email = 'unique@test.com';
        AdminUser::factory()->create(['email' => $email]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        AdminUser::factory()->create(['email' => $email]);
    }

    /**
     * Test AdminUser can create API tokens
     */
    public function test_admin_user_can_create_api_tokens()
    {
        $user = AdminUser::factory()->create();

        $this->assertTrue(method_exists($user, 'createToken'));
        $this->assertTrue(method_exists($user, 'tokens'));
    }

    /**
     * Test AdminUser permissions are stored as JSON
     */
    public function test_admin_user_permissions_stored_as_json()
    {
        $permissions = ['admin.*', 'users.read', 'posts.create'];
        $user = AdminUser::factory()->create([
            'permissions' => $permissions,
        ]);

        // Refresh from database
        $user = $user->fresh();

        $this->assertEquals($permissions, $user->permissions);
        $this->assertIsArray($user->permissions);
    }

    /**
     * Test AdminUser metadata is stored as JSON
     */
    public function test_admin_user_metadata_stored_as_json()
    {
        $metadata = [
            'role' => 'super_admin',
            'department' => 'IT',
            'last_password_change' => '2025-01-01',
        ];
        $user = AdminUser::factory()->create([
            'metadata' => $metadata,
        ]);

        // Refresh from database
        $user = $user->fresh();

        $this->assertEquals($metadata, $user->metadata);
        $this->assertIsArray($user->metadata);
    }

    /**
     * Test AdminUser table name is correct
     */
    public function test_admin_user_table_name_is_correct()
    {
        $user = new AdminUser();

        $this->assertEquals('admin_users', $user->getTable());
    }

    /**
     * Test AdminUser primary key configuration
     */
    public function test_admin_user_primary_key_configuration()
    {
        $user = new AdminUser();

        $this->assertEquals('id', $user->getKeyName());
        $this->assertEquals('string', $user->getKeyType());
        $this->assertFalse($user->getIncrementing());
    }

    /**
     * Test AdminUser password verification
     */
    public function test_admin_user_password_verification()
    {
        $password = 'password123';
        $user = AdminUser::factory()->create([
            'password_hash' => Hash::make($password),
        ]);

        $this->assertTrue(Hash::check($password, $user->password_hash));
        $this->assertFalse(Hash::check('wrongpassword', $user->password_hash));
    }

    /**
     * Test AdminUser can update last login
     */
    public function test_admin_user_can_update_last_login()
    {
        $user = AdminUser::factory()->create(['last_login' => null]);

        $this->assertNull($user->last_login);

        $user->update(['last_login' => now()]);

        $this->assertNotNull($user->fresh()->last_login);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->fresh()->last_login);
    }

    /**
     * Test AdminUser can create OAuth2 tokens with scopes
     */
    public function test_admin_user_can_create_oauth2_tokens_with_scopes()
    {
        $user = AdminUser::factory()->create();
        $scopes = ['read', 'write', 'users:read'];

        $this->assertTrue(method_exists($user, 'createToken'));
        
        // Create a token with specific scopes
        $token = $user->createToken('Test Token', $scopes);
        
        $this->assertNotNull($token);
        $this->assertNotEmpty($token->accessToken);
    }

    /**
     * Test AdminUser permission-based scope mapping
     */
    public function test_admin_user_permission_based_scope_mapping()
    {
        // Test user with specific permissions
        $user1 = AdminUser::factory()->create([
            'permissions' => ['users.read', 'users.create']
        ]);
        
        $this->assertIsArray($user1->permissions);
        $this->assertContains('users.read', $user1->permissions);
        $this->assertContains('users.create', $user1->permissions);

        // Test user with wildcard permissions
        $user2 = AdminUser::factory()->create([
            'permissions' => ['users.*']
        ]);
        
        $this->assertIsArray($user2->permissions);
        $this->assertContains('users.*', $user2->permissions);

        // Test super admin user
        $user3 = AdminUser::factory()->create([
            'permissions' => ['*']
        ]);
        
        $this->assertIsArray($user3->permissions);
        $this->assertContains('*', $user3->permissions);
    }

    /**
     * Test AdminUser with complex permission structures
     */
    public function test_admin_user_with_complex_permission_structures()
    {
        $complexPermissions = [
            'users.read',
            'users.create',
            'personas.*',
            'chat.read',
            'knowledge.*',
            'snippets.delete'
        ];

        $user = AdminUser::factory()->create([
            'permissions' => $complexPermissions
        ]);

        $this->assertEquals($complexPermissions, $user->permissions);
        $this->assertCount(6, $user->permissions);
        
        // Verify it can be serialized/deserialized properly
        $user = $user->fresh();
        $this->assertEquals($complexPermissions, $user->permissions);
    }

    /**
     * Test AdminUser tenant relationships
     */
    public function test_admin_user_tenant_relationships()
    {
        $user = AdminUser::factory()->create();
        
        // Check if the user has a tenants relationship method
        $this->assertTrue(method_exists($user, 'tenants'));
        
        // The relationship should return a collection (even if empty)
        $tenants = $user->tenants;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tenants);
    }

    /**
     * Test AdminUser with empty permissions defaults to array
     */
    public function test_admin_user_with_empty_permissions_defaults_to_array()
    {
        $user = AdminUser::factory()->create([
            'permissions' => null
        ]);

        // Should cast null to empty array
        $this->assertIsArray($user->permissions);
        $this->assertEmpty($user->permissions);

        // Test with explicit empty array
        $user2 = AdminUser::factory()->create([
            'permissions' => []
        ]);

        $this->assertIsArray($user2->permissions);
        $this->assertEmpty($user2->permissions);
    }

    /**
     * Test AdminUser metadata with OAuth-related data
     */
    public function test_admin_user_metadata_with_oauth_related_data()
    {
        $metadata = [
            'oauth_scopes' => ['read', 'write', 'users:read'],
            'preferred_scopes' => ['users:read', 'personas:write'],
            'last_token_created' => '2025-09-15 10:00:00',
            'api_usage_count' => 150
        ];

        $user = AdminUser::factory()->create([
            'metadata' => $metadata
        ]);

        $this->assertEquals($metadata, $user->metadata);
        $this->assertIsArray($user->metadata);
        $this->assertEquals(['read', 'write', 'users:read'], $user->metadata['oauth_scopes']);
    }

    /**
     * Test AdminUser authentication with OAuth2 context
     */
    public function test_admin_user_authentication_with_oauth2_context()
    {
        $user = AdminUser::factory()->create([
            'email' => 'oauth@test.com',
            'password_hash' => \Illuminate\Support\Facades\Hash::make('password123'),
            'permissions' => ['users.read', 'chat.*'],
            'is_active' => true
        ]);

        // Verify auth identifier methods work correctly
        $this->assertEquals($user->id, $user->getAuthIdentifier());
        $this->assertEquals('id', $user->getAuthIdentifierName());
        
        // Verify password verification works
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password123', $user->getAuthPassword()));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('wrongpassword', $user->getAuthPassword()));
    }

    /**
     * Test AdminUser last_updated timestamp functionality
     */
    public function test_admin_user_last_updated_timestamp_functionality()
    {
        $user = AdminUser::factory()->create([
            'last_updated' => null
        ]);

        $this->assertNull($user->last_updated);

        // Update the user and verify last_updated is set
        $user->update([
            'last_updated' => now(),
            'metadata' => ['test' => 'value']
        ]);

        $updatedUser = $user->fresh();
        $this->assertNotNull($updatedUser->last_updated);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $updatedUser->last_updated);
    }
}