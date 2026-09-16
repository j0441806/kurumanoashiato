<?php

namespace Tests\Feature\Models;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_car_with_its_user_and_category_cast(): void
    {
        $car = Car::factory()->create([
            'category' => CarCategory::Rental,
        ]);

        $this->assertInstanceOf(User::class, $car->user);
        $this->assertSame($car->user_id, $car->user->id);
        $this->assertSame(CarCategory::Rental, $car->category);
    }

    public function test_user_can_create_and_retrieve_cars_through_the_relationship(): void
    {
        $user = User::factory()->create();

        $car = $user->cars()->create([
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'category' => CarCategory::Owned,
        ]);

        $this->assertSame($user->id, $car->user_id);
        $this->assertTrue($user->cars->contains($car));
    }

    public function test_deleting_a_car_soft_deletes_it(): void
    {
        $car = Car::factory()->create();

        $car->delete();

        $this->assertSoftDeleted($car);
        $this->assertNull(Car::query()->find($car->id));
        $this->assertTrue(Car::withTrashed()->findOrFail($car->id)->trashed());
    }
}
