<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GroupFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'description' => $this->faker->sentence(),
            'created_by'  => User::factory(),
            'is_private'  => false,
        ];
    }
}
