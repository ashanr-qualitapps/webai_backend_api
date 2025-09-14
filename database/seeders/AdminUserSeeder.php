<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminUser::create([
            'id' => Str::uuid(),
            'email' => 'admin@webai.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Super Admin',
            'permissions' => json_encode(['*']), // Wildcard permission for super admin
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AdminUser::create([
            'id' => Str::uuid(),
            'email' => 'user@webai.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Regular User',
            'permissions' => json_encode([
                'users.read',
                'knowledge.read',
                'chat.read',
                'chat.create',
                'personas.read',
                'snippets.read',
                'suggestions.read'
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AdminUser::create([
            'id' => Str::uuid(),
            'email' => 'manager@webai.com',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Content Manager',
            'permissions' => json_encode([
                'users.read',
                'knowledge.*', // All knowledge base permissions
                'chat.*',      // All chat permissions
                'personas.*',  // All persona permissions
                'snippets.*',  // All snippet permissions
                'suggestions.*' // All suggestion permissions
            ]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
