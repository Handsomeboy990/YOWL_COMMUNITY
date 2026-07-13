<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => fake()->paragraph(),
            'link' => fake()->url(),
            'medias' => [],
            'nb_like' => 0,
            'nb_dislike' => 0,
            'nb_views' => 0,
            'is_published' => true,
        ];
    }

    /**
     * Indicate that the review is hidden from the public feed.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
