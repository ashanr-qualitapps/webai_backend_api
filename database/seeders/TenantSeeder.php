<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = [
            [
                'name' => 'Acme Corporation',
                'domain' => 'acme.com',
                'app_key' => '550e8400-e29b-41d4-a716-446655440001', // Fixed UUID for testing
                'is_active' => true,
                'settings' => [
                    'theme' => 'dark',
                    'features' => ['chat', 'ai', 'analytics'],
                    'branding' => [
                        'logo' => 'acme-logo.png',
                        'primaryColor' => '#1F2937'
                    ]
                ]
            ],
            [
                'name' => 'TechStart Inc',
                'domain' => 'techstart.io',
                'app_key' => '550e8400-e29b-41d4-a716-446655440002', // Fixed UUID for testing
                'is_active' => true,
                'settings' => [
                    'theme' => 'light',
                    'features' => ['chat', 'ai'],
                    'branding' => [
                        'logo' => 'techstart-logo.png',
                        'primaryColor' => '#3B82F6'
                    ]
                ]
            ],
            [
                'name' => 'Global Solutions',
                'domain' => 'globalsolutions.net',
                'app_key' => '550e8400-e29b-41d4-a716-446655440003', // Fixed UUID for testing
                'is_active' => true,
                'settings' => [
                    'theme' => 'auto',
                    'features' => ['chat', 'ai', 'analytics', 'reports'],
                    'branding' => [
                        'logo' => 'global-logo.png',
                        'primaryColor' => '#10B981'
                    ]
                ]
            ],
            [
                'name' => 'Demo Company',
                'domain' => 'localhost',
                'app_key' => '550e8400-e29b-41d4-a716-446655440004', // Fixed UUID for testing
                'is_active' => true,
                'settings' => [
                    'theme' => 'light',
                    'features' => ['chat', 'ai'],
                    'branding' => [
                        'logo' => 'demo-logo.png',
                        'primaryColor' => '#8B5CF6'
                    ]
                ]
            ],
            [
                'name' => 'Development Environment',
                'domain' => '127.0.0.1',
                'app_key' => '550e8400-e29b-41d4-a716-446655440005', // Fixed UUID for testing
                'is_active' => true,
                'settings' => [
                    'theme' => 'dark',
                    'features' => ['chat', 'ai', 'debug'],
                    'branding' => [
                        'logo' => 'dev-logo.png',
                        'primaryColor' => '#EF4444'
                    ]
                ]
            ]
        ];

        foreach ($tenants as $tenantData) {
            Tenant::firstOrCreate(
                ['app_key' => $tenantData['app_key']], // Use app_key instead of domain for uniqueness
                $tenantData
            );
        }

        $this->command->info('Tenants seeded successfully!');
    }
}