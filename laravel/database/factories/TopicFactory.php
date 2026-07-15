<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TopicFactory extends Factory
{
    public function definition(): array
    {
        $title = $this->faker->unique()->sentence(5);
        return [
            'group_id' => Group::factory(),
            'user_id'  => User::factory(),
            'title'    => $title,
            'slug'     => Str::slug($title) . '-' . Str::random(4),
            'body'     => $this->faker->paragraph(),
            'is_pinned' => false,
            'is_locked' => false,
            'views'     => 0,
        ];
    }
}
