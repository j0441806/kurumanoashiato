<?php

namespace Database\Factories;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\DriveRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DriveRecord> */
class DriveRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'drive_date' => fake()->dateTimeBetween('-1 year'),
            'departure' => fake()->city(),
            'destination' => fake()->city(),
            'spots' => fake()->optional()->city(),
            'road_types' => fake()->randomElements(RoadType::cases(), fake()->numberBetween(1, 3)),
            'distance' => fake()->randomFloat(1, 0, 99999.9),
            'note_theme' => fake()->optional()->sentence(),
            'note_good_points' => fake()->optional()->sentence(),
            'note_issues' => fake()->optional()->sentence(),
            'note_next_action' => fake()->optional()->sentence(),
            'note_free' => fake()->optional()->paragraph(),
        ];
    }
}
