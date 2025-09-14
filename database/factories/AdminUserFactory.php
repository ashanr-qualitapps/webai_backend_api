<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminUser>
 */
class AdminUserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => $this->faker->unique()->safeEmail(),
            'password_hash' => bcrypt('password123'),
            'full_name' => $this->faker->name(),
            'permissions' => ['admin.*'],
            'metadata' => null,
            'updated_by' => null,
            'last_login' => null,
            'is_active' => true,
            'last_updated' => now(),
        ];
    }
}
