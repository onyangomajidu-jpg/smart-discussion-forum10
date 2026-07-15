<?php

namespace Database\Factories;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'topic_id'       => Topic::factory(),
            'user_id'        => User::factory(),
            'body'           => $this->faker->paragraph(),
            'is_best_answer' => false,
            'upvotes'        => 0,
            'downvotes'      => 0,
        ];
    }
}
