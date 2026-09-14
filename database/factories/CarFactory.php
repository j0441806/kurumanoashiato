<?php

namespace Database\Factories;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Car> */
class CarFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'maker' => fake()->company(),
            'model' => fake()->word(),
            'grade' => fake()->optional()->word(),
            'category' => fake()->randomElement(CarCategory::cases()),
            'station' => fake()->optional()->address(),
            'memo' => fake()->optional()->sentence(),
        ];
    }
}
