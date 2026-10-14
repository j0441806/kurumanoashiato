<?php

namespace Tests\Feature\Cars;

use App\Enums\CarCategory;
use App\Livewire\Cars\EditCar;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EditCarTest extends TestCase
{
    use RefreshDatabase;

    private const STATION_DELETION_WARNING = '保存すると、登録済みのステーション情報は削除されます。';

    public function test_guest_is_redirected_to_login(): void
    {
        $car = Car::factory()->create();

        $this->get(route('cars.edit', $car))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_edit_screen_with_current_values(): void
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

        $this->actingAs($user);

        $component = Livewire::test(EditCar::class, ['car' => $car])
            ->assertSet('maker', 'Subaru')
            ->assertSet('model', 'Levorg')
            ->assertSet('grade', 'STI Sport R EX')
            ->assertSet('category', CarCategory::Rental->value)
            ->assertSet('station', '新宿駅前')
            ->assertSet('memo', '長距離ドライブ用');

        foreach (CarCategory::cases() as $category) {
            $component->assertSee($category->label());
        }
    }

    public function test_user_cannot_edit_another_users_car(): void
    {
        $user = User::factory()->create();
        $otherUsersCar = Car::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.edit', $otherUsersCar))
            ->assertNotFound();
    }

    public function test_nonexistent_car_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('cars.edit', 999999))
            ->assertNotFound();
    }

    public function test_car_can_be_updated_and_redirects_to_details(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->fill($this->validInput())
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cars.show', $car, absolute: false));

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'user_id' => $user->id,
            'maker' => 'Toyota',
            'model' => 'Yaris',
            'grade' => 'Z',
            'category' => CarCategory::Rental->value,
            'station' => '新宿駅前',
            'memo' => '街乗り用',
        ]);
    }

    public function test_required_fields_are_validated_without_updating_the_car(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Original Maker',
            'model' => 'Original Model',
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->set('maker', '')
            ->set('model', '')
            ->set('category', null)
            ->call('save')
            ->assertHasErrors([
                'maker' => 'required',
                'model' => 'required',
                'category' => 'required',
            ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'maker' => 'Original Maker',
            'model' => 'Original Model',
        ]);
    }

    public function test_unknown_category_is_rejected_without_updating_the_car(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'category' => CarCategory::Owned,
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->set('category', 999)
            ->call('save')
            ->assertHasErrors(['category']);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'category' => CarCategory::Owned->value,
        ]);
    }

    #[DataProvider('categoriesRequiringStation')]
    public function test_station_is_required_for_non_owned_categories(CarCategory $category): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'category' => CarCategory::Owned,
            'station' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->set('category', $category->value)
            ->set('station', '')
            ->call('save')
            ->assertHasErrors(['station' => 'required']);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'category' => CarCategory::Owned->value,
            'station' => null,
        ]);
    }

    public function test_owned_car_ignores_retained_station_during_validation_and_saves_null(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'category' => CarCategory::Rental,
            'station' => '新宿駅前',
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->set('category', CarCategory::Owned->value)
            ->set('station', str_repeat('a', 256))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cars.show', $car, absolute: false));

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'category' => CarCategory::Owned->value,
            'station' => null,
        ]);
    }

    public function test_station_value_is_preserved_when_category_is_changed_without_saving(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'category' => CarCategory::Rental,
            'station' => '新宿駅前',
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->assertDontSee(self::STATION_DELETION_WARNING)
            ->set('station', '東京駅前')
            ->set('category', CarCategory::Owned->value)
            ->assertSet('station', '東京駅前')
            ->assertSee(self::STATION_DELETION_WARNING)
            ->assertSeeHtml('disabled')
            ->set('category', CarCategory::CarShare->value)
            ->assertSet('station', '東京駅前')
            ->assertDontSee(self::STATION_DELETION_WARNING);
    }

    public function test_station_deletion_warning_is_not_shown_without_a_persisted_station(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'category' => CarCategory::Owned,
            'station' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->assertDontSee(self::STATION_DELETION_WARNING);
    }

    #[DataProvider('oversizedInput')]
    public function test_oversized_input_is_rejected_without_updating_the_car(string $field, string $value): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Original Maker',
            'category' => CarCategory::Rental,
            'station' => '新宿駅前',
        ]);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->fill([
                ...$this->validInput(),
                $field => $value,
            ])
            ->call('save')
            ->assertHasErrors([$field => 'max']);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'maker' => 'Original Maker',
        ]);
    }

    public function test_memo_with_ten_thousand_characters_can_be_saved(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $memo = str_repeat('あ', 10000);

        $this->actingAs($user);

        Livewire::test(EditCar::class, ['car' => $car])
            ->fill([
                ...$this->validInput(),
                'memo' => $memo,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'memo' => $memo,
        ]);
    }

    public static function categoriesRequiringStation(): array
    {
        return [
            'rental' => [CarCategory::Rental],
            'car share' => [CarCategory::CarShare],
        ];
    }

    public static function oversizedInput(): array
    {
        return [
            'maker' => ['maker', str_repeat('a', 101)],
            'model' => ['model', str_repeat('a', 101)],
            'grade' => ['grade', str_repeat('a', 101)],
            'station' => ['station', str_repeat('a', 256)],
            'memo' => ['memo', str_repeat('a', 10001)],
        ];
    }

    private function validInput(): array
    {
        return [
            'maker' => 'Toyota',
            'model' => 'Yaris',
            'grade' => 'Z',
            'category' => CarCategory::Rental->value,
            'station' => '新宿駅前',
            'memo' => '街乗り用',
        ];
    }
}
