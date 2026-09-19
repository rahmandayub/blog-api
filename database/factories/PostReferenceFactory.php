<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostReference>
 */
class PostReferenceFactory extends Factory
{
    protected $model = PostReference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'title' => fake()->sentence(3),
            'url' => fake()->url(),
            'source' => fake()->company(),
            'sort_order' => 0,
        ];
    }
}
