<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Photo> */
class PhotoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'drive_record_id' => null,
            'file_path' => 'photos/'.fake()->uuid().'.jpg',
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function forDriveRecord(): static
    {
        return $this->state(fn (array $attributes) => [
            'car_id' => null,
            'drive_record_id' => DriveRecord::factory(),
        ]);
    }
}
