<?php

namespace Tests\Feature\Cars;

use App\Enums\CarCategory;
use App\Livewire\Cars\CreateCar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateCarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cars.create'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_registration_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('cars.create'));

        $response->assertOk();

        foreach (CarCategory::cases() as $category) {
            $response->assertSee($category->label());
        }
    }

    public function test_station_input_is_shown_only_for_categories_that_require_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->assertDontSee('ステーション')
            ->set('category', CarCategory::Rental->value)
            ->assertSee('ステーション')
            ->set('category', CarCategory::Owned->value)
            ->assertDontSee('ステーション');
    }

    #[DataProvider('carCategories')]
    public function test_car_can_be_registered_with_a_valid_category(CarCategory $category): void
    {
        $user = User::factory()->create();
        $station = $category === CarCategory::Owned ? null : '新宿駅前';

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput($category, $station))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cars.index', absolute: false));

        $this->assertDatabaseHas('cars', [
            'user_id' => $user->id,
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'category' => $category->value,
            'station' => $station,
        ]);
    }

    public function test_owned_car_discards_a_submitted_station(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput(CarCategory::Owned, '自宅'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cars', [
            'user_id' => $user->id,
            'category' => CarCategory::Owned->value,
            'station' => null,
        ]);
    }

    #[DataProvider('categoriesRequiringStation')]
    public function test_station_is_required_for_non_owned_categories(CarCategory $category): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput($category, null))
            ->call('save')
            ->assertHasErrors(['station' => 'required']);

        $this->assertDatabaseCount('cars', 0);
    }

    public function test_memo_with_ten_thousand_characters_can_be_registered(): void
    {
        $user = User::factory()->create();
        $memo = str_repeat('あ', 10000);

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill([
                ...$this->validInput(),
                'memo' => $memo,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cars', [
            'user_id' => $user->id,
            'memo' => $memo,
        ]);
    }

    public function test_memo_longer_than_ten_thousand_characters_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill([
                ...$this->validInput(),
                'memo' => str_repeat('あ', 10001),
            ])
            ->call('save')
            ->assertHasErrors(['memo' => 'max']);

        $this->assertDatabaseCount('cars', 0);
    }

    public function test_unknown_category_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill([
                ...$this->validInput(),
                'category' => 999,
            ])
            ->call('save')
            ->assertHasErrors(['category']);

        $this->assertDatabaseCount('cars', 0);
    }

    public function test_required_fields_are_validated_without_saving_a_car(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->call('save')
            ->assertHasErrors([
                'maker' => 'required',
                'model' => 'required',
                'category' => 'required',
            ]);

        $this->assertDatabaseCount('cars', 0);
    }

    #[DataProvider('oversizedInput')]
    public function test_oversized_input_is_rejected(string $field, string $value): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill([
                ...$this->validInput(CarCategory::Rental, '新宿駅前'),
                $field => $value,
            ])
            ->call('save')
            ->assertHasErrors([$field => 'max']);

        $this->assertDatabaseCount('cars', 0);
    }

    public static function carCategories(): array
    {
        return [
            'owned' => [CarCategory::Owned],
            'rental' => [CarCategory::Rental],
            'car share' => [CarCategory::CarShare],
        ];
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
        ];
    }

    private function validInput(
        CarCategory $category = CarCategory::Owned,
        ?string $station = null,
    ): array {
        return [
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'grade' => 'STI Sport R EX',
            'category' => $category->value,
            'station' => $station ?? '',
            'memo' => '長距離ドライブ用',
        ];
    }
}
