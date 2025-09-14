<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\RateLimitAuth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RateLimitAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private RateLimitAuth $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RateLimitAuth(app('Illuminate\Cache\RateLimiter'));
    }

    /**
     * Test middleware allows requests under rate limit
     */
    public function test_middleware_allows_requests_under_rate_limit()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * Test middleware blocks requests over rate limit
     */
    public function test_middleware_blocks_requests_over_rate_limit()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // Simulate hitting rate limit by making multiple requests
        $key = 'auth_attempts:127.0.0.1';
        RateLimiter::hit($key, 15 * 60); // 15 minutes decay
        RateLimiter::hit($key, 15 * 60);
        RateLimiter::hit($key, 15 * 60);
        RateLimiter::hit($key, 15 * 60);
        RateLimiter::hit($key, 15 * 60);

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(false, $content['success']);
        $this->assertEquals('Too many authentication attempts. Please try again later.', $content['message']);
        $this->assertArrayHasKey('retry_after', $content);
    }

    /**
     * Test middleware returns correct retry after time
     */
    public function test_middleware_returns_correct_retry_after_time()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // Hit rate limit
        $key = 'auth_attempts:127.0.0.1';
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 15 * 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('retry_after', $content);
        $this->assertIsInt($content['retry_after']);
        $this->assertGreaterThan(0, $content['retry_after']);
        $this->assertLessThanOrEqual(15 * 60, $content['retry_after']); // Should be <= 15 minutes
    }

    /**
     * Test middleware uses IP address for rate limiting key
     */
    public function test_middleware_uses_ip_address_for_rate_limiting_key()
    {
        $ip1 = '192.168.1.1';
        $ip2 = '192.168.1.2';

        // Make 5 requests from first IP
        $request1 = Request::create('/api/v1/login', 'POST');
        $request1->server->set('REMOTE_ADDR', $ip1);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->middleware->handle($request1, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response->getStatusCode());
        }

        // 6th request from first IP should be blocked
        $response = $this->middleware->handle($request1, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(429, $response->getStatusCode());

        // Request from second IP should still be allowed
        $request2 = Request::create('/api/v1/login', 'POST');
        $request2->server->set('REMOTE_ADDR', $ip2);

        $response = $this->middleware->handle($request2, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware rate limit configuration
     */
    public function test_middleware_rate_limit_configuration()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        // Test that exactly 5 requests are allowed
        for ($i = 0; $i < 5; $i++) {
            $response = $this->middleware->handle($request, function ($req) {
                return new Response('OK', 200);
            });
            $this->assertEquals(200, $response->getStatusCode(), "Request $i should be allowed");
        }

        // 6th request should be blocked
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(429, $response->getStatusCode(), "6th request should be blocked");
    }

    /**
     * Test middleware handles requests without IP address
     */
    public function test_middleware_handles_requests_without_ip_address()
    {
        $request = Request::create('/api/v1/login', 'POST');
        // Don't set REMOTE_ADDR

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Should still work, probably using a default IP or request identifier
        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * Test middleware clears rate limit after decay time
     */
    public function test_middleware_clears_rate_limit_after_decay_time()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $key = 'auth_attempts:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 1); // 1 second decay for testing
        }

        // Should be blocked
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(429, $response->getStatusCode());

        // Wait for decay (simulate by clearing the rate limiter)
        RateLimiter::clear($key);

        // Should be allowed again
        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test middleware increments attempt counter
     */
    public function test_middleware_increments_attempt_counter()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $key = 'auth_attempts:127.0.0.1';

        // Clear any existing attempts
        RateLimiter::clear($key);

        // Check initial state
        $this->assertEquals(0, RateLimiter::attempts($key));

        // Make a request
        $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        // Check attempts incremented
        $this->assertEquals(1, RateLimiter::attempts($key));
    }

    /**
     * Test middleware JSON response format
     */
    public function test_middleware_json_response_format()
    {
        $request = Request::create('/api/v1/login', 'POST');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $key = 'auth_attempts:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 15 * 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('OK', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('success', $content);
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('retry_after', $content);
        $this->assertEquals(false, $content['success']);
        $this->assertIsString($content['message']);
        $this->assertIsInt($content['retry_after']);
    }
}