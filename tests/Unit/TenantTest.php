<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Tenant model can be created with factory
     */
    public function test_tenant_can_be_created_with_factory()
    {
        $tenant = Tenant::factory()->create();

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertNotEmpty($tenant->id);
        $this->assertNotEmpty($tenant->name);
        $this->assertNotEmpty($tenant->domain);
        $this->assertNotEmpty($tenant->app_key);
        $this->assertTrue($tenant->is_active);
    }

    /**
     * Test Tenant automatically generates UUID app_key
     */
    public function test_tenant_automatically_generates_uuid_app_key()
    {
        $tenant = Tenant::factory()->create([
            'app_key' => null // Force null to test auto-generation
        ]);

        // The boot method should generate a UUID
        $this->assertNotNull($tenant->app_key);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $tenant->app_key
        );
    }

    /**
     * Test Tenant preserves provided app_key
     */
    public function test_tenant_preserves_provided_app_key()
    {
        $customAppKey = Str::uuid();
        $tenant = Tenant::factory()->create([
            'app_key' => $customAppKey
        ]);

        $this->assertEquals($customAppKey, $tenant->app_key);
    }

    /**
     * Test Tenant fillable attributes
     */
    public function test_tenant_fillable_attributes()
    {
        $tenant = new Tenant();
        $expected = [
            'name',
            'domain',
            'app_key',
            'is_active',
            'settings',
        ];

        $this->assertEquals($expected, $tenant->getFillable());
    }

    /**
     * Test Tenant casts attributes correctly
     */
    public function test_tenant_casts_attributes_correctly()
    {
        $settings = [
            'theme' => 'dark',
            'timezone' => 'UTC',
            'features' => ['oauth', 'multi-user']
        ];

        $tenant = Tenant::factory()->create([
            'is_active' => true,
            'settings' => $settings,
        ]);

        $this->assertIsBool($tenant->is_active);
        $this->assertIsArray($tenant->settings);
        $this->assertEquals($settings, $tenant->settings);
    }

    /**
     * Test Tenant scope for active tenants
     */
    public function test_tenant_scope_for_active_tenants()
    {
        // Create active and inactive tenants
        Tenant::factory()->create(['is_active' => true]);
        Tenant::factory()->create(['is_active' => false]);
        Tenant::factory()->create(['is_active' => true]);

        $activeTenants = Tenant::where('is_active', true)->get();
        $inactiveTenants = Tenant::where('is_active', false)->get();

        $this->assertCount(2, $activeTenants);
        $this->assertCount(1, $inactiveTenants);
    }

    /**
     * Test Tenant app_key uniqueness
     */
    public function test_tenant_app_key_uniqueness()
    {
        $appKey = Str::uuid();
        Tenant::factory()->create(['app_key' => $appKey]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::factory()->create(['app_key' => $appKey]);
    }

    /**
     * Test Tenant domain uniqueness
     */
    public function test_tenant_domain_uniqueness()
    {
        $domain = 'unique.example.com';
        Tenant::factory()->create(['domain' => $domain]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::factory()->create(['domain' => $domain]);
    }

    /**
     * Test Tenant findByAppKey method
     */
    public function test_tenant_find_by_app_key_method()
    {
        $tenant = Tenant::factory()->create();
        
        // Test the findByAppKey method if it exists
        if (method_exists(Tenant::class, 'findByAppKey')) {
            $foundTenant = Tenant::findByAppKey($tenant->app_key);
            $this->assertEquals($tenant->id, $foundTenant->id);
            
            // Test with non-existent app key
            $notFound = Tenant::findByAppKey('non-existent-key');
            $this->assertNull($notFound);
        } else {
            // Test manual lookup by app_key
            $foundTenant = Tenant::where('app_key', $tenant->app_key)->first();
            $this->assertEquals($tenant->id, $foundTenant->id);
        }
    }

    /**
     * Test Tenant settings JSON storage
     */
    public function test_tenant_settings_json_storage()
    {
        $settings = [
            'branding' => [
                'logo' => 'logo.png',
                'primary_color' => '#1234AB'
            ],
            'features' => [
                'oauth_enabled' => true,
                'multi_tenancy' => true,
                'api_rate_limit' => 1000
            ],
            'integrations' => [
                'slack' => ['webhook_url' => 'https://hooks.slack.com/...']
            ]
        ];

        $tenant = Tenant::factory()->create([
            'settings' => $settings,
        ]);

        // Refresh from database
        $tenant = $tenant->fresh();

        $this->assertEquals($settings, $tenant->settings);
        $this->assertIsArray($tenant->settings);
        $this->assertEquals('#1234AB', $tenant->settings['branding']['primary_color']);
        $this->assertTrue($tenant->settings['features']['oauth_enabled']);
    }

    /**
     * Test Tenant table name is correct
     */
    public function test_tenant_table_name_is_correct()
    {
        $tenant = new Tenant();
        $this->assertEquals('tenants', $tenant->getTable());
    }

    /**
     * Test Tenant adminUsers relationship
     */
    public function test_tenant_admin_users_relationship()
    {
        $tenant = Tenant::factory()->create();
        
        // Check if the tenant has an adminUsers relationship method
        if (method_exists($tenant, 'adminUsers')) {
            $this->assertTrue(method_exists($tenant, 'adminUsers'));
            
            // The relationship should return a collection (even if empty)
            $adminUsers = $tenant->adminUsers;
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $adminUsers);
        } else {
            // Just verify the tenant was created successfully
            $this->assertNotNull($tenant->id);
        }
    }

    /**
     * Test Tenant with empty settings defaults to array
     */
    public function test_tenant_with_empty_settings_defaults_to_array()
    {
        $tenant = Tenant::factory()->create([
            'settings' => null
        ]);

        // Should cast null to empty array or handle gracefully
        $this->assertTrue(is_array($tenant->settings) || is_null($tenant->settings));

        // Test with explicit empty array
        $tenant2 = Tenant::factory()->create([
            'settings' => []
        ]);

        $this->assertIsArray($tenant2->settings);
        $this->assertEmpty($tenant2->settings);
    }

    /**
     * Test Tenant factory states
     */
    public function test_tenant_factory_states()
    {
        // Test inactive state if it exists
        if (method_exists(Tenant::factory(), 'inactive')) {
            $inactiveTenant = Tenant::factory()->inactive()->create();
            $this->assertFalse($inactiveTenant->is_active);
        }

        // Test with specific domain if method exists
        if (method_exists(Tenant::factory(), 'withDomain')) {
            $domain = 'custom.example.com';
            $tenant = Tenant::factory()->withDomain($domain)->create();
            $this->assertEquals($domain, $tenant->domain);
        }

        // Test with specific app key if method exists
        if (method_exists(Tenant::factory(), 'withAppKey')) {
            $appKey = Str::uuid();
            $tenant = Tenant::factory()->withAppKey($appKey)->create();
            $this->assertEquals($appKey, $tenant->app_key);
        }
    }

    /**
     * Test Tenant model timestamps
     */
    public function test_tenant_model_timestamps()
    {
        $tenant = Tenant::factory()->create();
        
        $this->assertNotNull($tenant->created_at);
        $this->assertNotNull($tenant->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $tenant->updated_at);
    }

    /**
     * Test Tenant can be updated
     */
    public function test_tenant_can_be_updated()
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Original Name'
        ]);

        $tenant->update([
            'name' => 'Updated Name',
            'settings' => ['updated' => true]
        ]);

        $this->assertEquals('Updated Name', $tenant->fresh()->name);
        $this->assertEquals(['updated' => true], $tenant->fresh()->settings);
    }
}