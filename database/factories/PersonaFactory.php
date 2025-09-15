<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\Snippet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Persona>
 */
class PersonaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Persona::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'title' => $this->faker->jobTitle(),
            'profile_picture_url' => $this->faker->imageUrl(200, 200, 'people'),
            'ai_expertise_description' => $this->faker->paragraph(3),
            'associated_profile_snippet_id' => null, // Will be set via relationship factory state
            'is_active' => $this->faker->boolean(80), // 80% chance of being active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the persona is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the persona is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the persona has an associated snippet.
     */
    public function withSnippet(): static
    {
        return $this->state(fn (array $attributes) => [
            'associated_profile_snippet_id' => Snippet::factory()->create()->id,
        ]);
    }

    /**
     * Indicate that the persona has no profile picture.
     */
    public function withoutProfilePicture(): static
    {
        return $this->state(fn (array $attributes) => [
            'profile_picture_url' => null,
        ]);
    }

    /**
     * Indicate that the persona has minimal data.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => null,
            'profile_picture_url' => null,
            'ai_expertise_description' => null,
            'associated_profile_snippet_id' => null,
        ]);
    }
}