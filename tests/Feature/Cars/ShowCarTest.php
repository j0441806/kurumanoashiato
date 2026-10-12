<?php

namespace Tests\Feature\Cars;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowCarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $car = Car::factory()->create();

        $this->get(route('cars.show', $car))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_car_details_when_nullable_fields_have_values(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'grade' => 'STI Sport R EX',
            'category' => CarCategory::Rental,
            'station' => '新宿駅前',
            'memo' => '長距離ドライブ用',
        ]);

        $this->actingAs($user)
            ->get(route('cars.show', $car))
            ->assertOk()
            ->assertSeeInOrder([
                'メーカー',
                'Subaru',
                '車種',
                'Levorg',
                'グレード',
                'STI Sport R EX',
                '車両区分',
                CarCategory::Rental->label(),
                'ステーション',
                '新宿駅前',
                'メモ',
                '長距離ドライブ用',
            ]);
    }

    public function test_nullable_values_are_shown_as_hyphens_when_absent(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'grade' => null,
            'station' => null,
            'memo' => null,
        ]);

        $this->actingAs($user)
            ->get(route('cars.show', $car))
            ->assertOk()
            ->assertSeeInOrder([
                'グレード',
                '-',
                'ステーション',
                '-',
                'メモ',
                '-',
            ]);
    }

    public function test_user_cannot_view_another_users_car(): void
    {
        $user = User::factory()->create();
        $otherUsersCar = Car::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.show', $otherUsersCar))
            ->assertNotFound();
    }

    public function test_nonexistent_car_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.show', 999999))
            ->assertNotFound();
    }
}
