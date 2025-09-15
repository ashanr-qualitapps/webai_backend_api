<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\AdminUser;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Setup for tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Run migrations for test database
        $this->artisan('migrate');
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
}
