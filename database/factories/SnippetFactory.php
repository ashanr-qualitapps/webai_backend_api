<?php

namespace Database\Factories;

use App\Models\Snippet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Snippet>
 */
class SnippetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Snippet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'identifier' => $this->faker->unique()->slug(2),
            'collapsed_html' => '<p>' . $this->faker->sentence() . '</p>',
            'expanded_html' => '<div>' . $this->faker->paragraph(3) . '</div>',
            'ai_explanation' => $this->faker->paragraph(2),
            'hyperlink_keywords' => $this->faker->words(3, true),
            'assigned_persona_id' => null,
            'confidence_threshold' => $this->faker->randomFloat(4, 0.5000, 0.9999),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the snippet is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the snippet has minimal data.
     */
    public function minimal(): static
    {
        return $this->state(fn (array $attributes) => [
            'collapsed_html' => null,
            'expanded_html' => null,
            'ai_explanation' => null,
            'hyperlink_keywords' => null,
            'confidence_threshold' => null,
        ]);
    }
}