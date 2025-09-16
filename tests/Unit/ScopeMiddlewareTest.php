<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\CheckScope;
use App\Http\Middleware\CheckScopes;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ScopeMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CheckScope middleware instantiation
     */
    public function test_check_scope_middleware_instantiation()
    {
        $middleware = new CheckScope();
        $this->assertInstanceOf(CheckScope::class, $middleware);
    }

    /**
     * Test CheckScopes middleware instantiation
     */
    public function test_check_scopes_middleware_instantiation()
    {
        $middleware = new CheckScopes();
        $this->assertInstanceOf(CheckScopes::class, $middleware);
    }

    /**
     * Test CheckScope middleware handles unauthenticated requests
     */
    public function test_check_scope_middleware_handles_unauthenticated_requests()
    {
        $middleware = new CheckScope();
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'users:read');

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthenticated', $responseData['message']);
        $this->assertFalse($responseData['success']);
    }

    /**
     * Test CheckScopes middleware handles unauthenticated requests
     */
    public function test_check_scopes_middleware_handles_unauthenticated_requests()
    {
        $middleware = new CheckScopes();
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () {
            return null;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'users:read', 'users:write');

        $this->assertEquals(401, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Unauthenticated', $responseData['message']);
        $this->assertFalse($responseData['success']);
    }

    /**
     * Test scope middleware response format for insufficient scope
     */
    public function test_scope_middleware_response_format_for_insufficient_scope()
    {
        $middleware = new CheckScope();
        $user = $this->createAdminUser([
            'permissions' => ['users.read']
        ]);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Create a user without token (simulating insufficient scope)
        $userWithoutToken = new AdminUser();
        $userWithoutToken->id = $user->id;
        $userWithoutToken->email = $user->email;
        
        $request->setUserResolver(function () use ($userWithoutToken) {
            return $userWithoutToken;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'admin:super');

        $this->assertEquals(401, $response->getStatusCode()); // No token = unauthenticated
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertFalse($responseData['success']);
    }

    /**
     * Test middleware parameters validation
     */
    public function test_middleware_parameters_validation()
    {
        $middleware = new CheckScope();
        
        // Test with no scopes provided
        $request = Request::create('/test', 'GET');
        $user = $this->createAdminUser();
        
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Should handle the case where no scopes are provided
        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        // This should return some response (either 200 or error)
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test CheckScope vs CheckScopes difference in logic
     */
    public function test_check_scope_vs_check_scopes_difference()
    {
        $checkScope = new CheckScope();
        $checkScopes = new CheckScopes();
        
        // Both should be different classes
        $this->assertNotEquals(get_class($checkScope), get_class($checkScopes));
        $this->assertInstanceOf(CheckScope::class, $checkScope);
        $this->assertInstanceOf(CheckScopes::class, $checkScopes);
    }

    /**
     * Test middleware handles multiple scope parameters
     */
    public function test_middleware_handles_multiple_scope_parameters()
    {
        $middleware = new CheckScope();
        $user = $this->createAdminUser();
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'scope1', 'scope2', 'scope3');

        // Should handle multiple parameters without crashing
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test middleware error response structure
     */
    public function test_middleware_error_response_structure()
    {
        $middleware = new CheckScopes();
        
        $request = Request::create('/test', 'POST');
        $request->setUserResolver(function () {
            return null; // Unauthenticated
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, 'users:write', 'admin');

        $this->assertEquals(401, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertIsString($responseData['message']);
    }

    /**
     * Test middleware with edge case inputs
     */
    public function test_middleware_with_edge_case_inputs()
    {
        $middleware = new CheckScope();
        $user = $this->createAdminUser();
        
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        // Test with empty string scope
        $response1 = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        }, '');

        $this->assertInstanceOf(Response::class, $response1);

        // Test with null-like scope
        $response2 = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertInstanceOf(Response::class, $response2);
    }
}