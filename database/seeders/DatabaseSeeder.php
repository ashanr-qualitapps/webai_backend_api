<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed tenants first (required for all other models)
        $this->call([
            TenantSeeder::class,
        ]);
        
        // Seed admin users for authentication testing
        $this->call([
            AdminUserSeeder::class,
        ]);

        // No normal users needed - using admin users only
    }
}
