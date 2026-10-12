<?php

namespace Tests\Feature\Cars;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexCarsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cars.index'))
            ->assertRedirect(route('login'));
    }

    public function test_empty_state_is_shown_when_user_has_no_cars(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.index'))
            ->assertOk()
            ->assertSee('まだ車両が登録されていません。');
    }

    public function test_only_authenticated_users_cars_are_shown_with_category_labels(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $cars = [
            Car::factory()->for($user)->create([
                'maker' => 'Subaru',
                'model' => 'Levorg',
                'category' => CarCategory::Owned,
            ]),
            Car::factory()->for($user)->create([
                'maker' => 'Toyota',
                'model' => 'Yaris',
                'category' => CarCategory::Rental,
            ]),
            Car::factory()->for($user)->create([
                'maker' => 'Nissan',
                'model' => 'Note',
                'category' => CarCategory::CarShare,
            ]),
        ];

        Car::factory()->for($otherUser)->create([
            'maker' => 'Other User Maker',
            'model' => 'Other User Model',
        ]);

        $response = $this->actingAs($user)->get(route('cars.index'));

        $response
            ->assertOk()
            ->assertSee('車両区分')
            ->assertDontSee('まだ車両が登録されていません。')
            ->assertDontSee('Other User Maker')
            ->assertDontSee('Other User Model');

        foreach ($cars as $car) {
            $response
                ->assertSee($car->maker)
                ->assertSee($car->model)
                ->assertSee($car->category->label())
                ->assertSee(route('cars.show', $car, absolute: false), escape: false);
        }
    }

    public function test_registration_screen_link_is_shown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.index'))
            ->assertOk()
            ->assertSee('車両を登録')
            ->assertSee(route('cars.create', absolute: false), escape: false);
    }
}
