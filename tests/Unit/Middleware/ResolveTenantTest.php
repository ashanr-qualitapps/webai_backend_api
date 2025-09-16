<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ResolveTenant;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Mockery;

class ResolveTenantTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ResolveTenant();
    }

    /**
     * Test middleware resolves tenant from X-App-Key header
     */
    public function test_middleware_resolves_tenant_from_app_key_header()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', $tenant->app_key);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        // Check that the resolved tenant is available in the request
        $this->assertInstanceOf(Tenant::class, $request->get('tenant'));
        $this->assertEquals($tenant->id, $request->get('tenant')->id);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware resolves tenant from app_key query parameter
     */
    public function test_middleware_resolves_tenant_from_app_key_query_parameter()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test?app_key=' . $tenant->app_key, 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertInstanceOf(Tenant::class, $request->get('tenant'));
        $this->assertEquals($tenant->id, $request->get('tenant')->id);
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware prioritizes header over query parameter
     */
    public function test_middleware_prioritizes_header_over_query_parameter()
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $request = Request::create('/api/test?app_key=' . $tenant2->app_key, 'GET');
        $request->headers->set('X-App-Key', $tenant1->app_key);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        // Should use the header value (tenant1), not query param (tenant2)
        $this->assertEquals($tenant1->id, $request->get('tenant')->id);
        $this->assertNotEquals($tenant2->id, $request->get('tenant')->id);
    }

    /**
     * Test middleware returns 401 when no app key provided
     */
    public function test_middleware_returns_401_when_no_app_key_provided()
    {
        $request = Request::create('/api/test', 'GET');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Missing or invalid app key', $responseData['error']);
    }

    /**
     * Test middleware returns 401 when invalid app key provided
     */
    public function test_middleware_returns_401_when_invalid_app_key_provided()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', 'invalid-app-key-12345');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Missing or invalid app key', $responseData['error']);
    }

    /**
     * Test middleware returns 401 when tenant is inactive
     */
    public function test_middleware_returns_401_when_tenant_is_inactive()
    {
        $tenant = Tenant::factory()->create(['is_active' => false]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', $tenant->app_key);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Tenant account is not active', $responseData['error']);
    }

    /**
     * Test middleware handles empty app key gracefully
     */
    public function test_middleware_handles_empty_app_key_gracefully()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', '');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Missing or invalid app key', $responseData['error']);
    }

    /**
     * Test middleware handles whitespace-only app key
     */
    public function test_middleware_handles_whitespace_only_app_key()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', '   ');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Missing or invalid app key', $responseData['error']);
    }

    /**
     * Test middleware trims app key before lookup
     */
    public function test_middleware_trims_app_key_before_lookup()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', '  ' . $tenant->app_key . '  ');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $request->get('tenant')->id);
    }

    /**
     * Test middleware case sensitivity of app key
     */
    public function test_middleware_case_sensitivity_of_app_key()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', strtoupper($tenant->app_key));

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        // App keys should be case sensitive, so uppercase should fail
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test middleware with POST request
     */
    public function test_middleware_with_post_request()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test', 'POST', [
            'data' => 'test'
        ]);
        $request->headers->set('X-App-Key', $tenant->app_key);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($tenant->id, $request->get('tenant')->id);
    }

    /**
     * Test middleware preserves original request data
     */
    public function test_middleware_preserves_original_request_data()
    {
        $tenant = Tenant::factory()->create();

        $requestData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'permissions' => ['read', 'write']
        ];

        $request = Request::create('/api/test', 'POST', $requestData);
        $request->headers->set('X-App-Key', $tenant->app_key);
        $request->headers->set('Authorization', 'Bearer test-token');

        $response = $this->middleware->handle($request, function ($req) use ($requestData) {
            // Verify original data is preserved
            $this->assertEquals($requestData['name'], $req->input('name'));
            $this->assertEquals($requestData['email'], $req->input('email'));
            $this->assertEquals($requestData['permissions'], $req->input('permissions'));
            $this->assertEquals('Bearer test-token', $req->header('Authorization'));
            
            return new Response('success');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware sets tenant in request attributes
     */
    public function test_middleware_sets_tenant_in_request_attributes()
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Test Tenant',
            'domain' => 'test.example.com',
            'settings' => ['feature_x' => true]
        ]);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', $tenant->app_key);

        $this->middleware->handle($request, function ($req) use ($tenant) {
            $resolvedTenant = $req->get('tenant');
            
            $this->assertInstanceOf(Tenant::class, $resolvedTenant);
            $this->assertEquals($tenant->id, $resolvedTenant->id);
            $this->assertEquals($tenant->name, $resolvedTenant->name);
            $this->assertEquals($tenant->domain, $resolvedTenant->domain);
            $this->assertEquals($tenant->app_key, $resolvedTenant->app_key);
            $this->assertEquals($tenant->settings, $resolvedTenant->settings);
            $this->assertTrue($resolvedTenant->is_active);
            
            return new Response('success');
        });
    }

    /**
     * Test middleware response format for errors
     */
    public function test_middleware_response_format_for_errors()
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', 'non-existent-key');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Unauthorized', $responseData['message']);
        $this->assertEquals('Missing or invalid app key', $responseData['error']);
    }

    /**
     * Test middleware performance with database query
     */
    public function test_middleware_performance_with_database_query()
    {
        $tenant = Tenant::factory()->create();

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('X-App-Key', $tenant->app_key);

        // Measure query count
        DB::enableQueryLog();

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('success');
        });

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertEquals(200, $response->getStatusCode());
        
        // Should perform exactly one query to find the tenant
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('tenants', $queries[0]['query']);
        $this->assertStringContainsString('app_key', $queries[0]['query']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}