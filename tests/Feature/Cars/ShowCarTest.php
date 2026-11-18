<?php

namespace Tests\Feature\Cars;

use App\Enums\CarCategory;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
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
            ])
            ->assertSee(route('cars.edit', $car, absolute: false), escape: false);
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

    public function test_registered_car_photos_and_notes_are_shown_in_id_order(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $firstPhoto = Photo::factory()->for($car)->create(['note' => "最初の写真\nメモ"]);
        $secondPhoto = Photo::factory()->for($car)->create(['note' => '次の写真']);

        $response = $this->actingAs($user)
            ->get(route('cars.show', $car));

        $response
            ->assertOk()
            ->assertSee('登録済み写真')
            ->assertSee("最初の写真\nメモ")
            ->assertSee('次の写真')
            ->assertSeeInOrder([
                route('cars.photos.show', [$car, $firstPhoto], absolute: false),
                route('cars.photos.show', [$car, $secondPhoto], absolute: false),
            ], escape: false);
    }

    public function test_photo_without_a_note_is_shown_as_no_note(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        Photo::factory()->for($car)->create(['note' => null]);

        $this->actingAs($user)
            ->get(route('cars.show', $car))
            ->assertOk()
            ->assertSee('メモなし');
    }

    public function test_empty_photo_state_is_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('cars.show', $car))
            ->assertOk()
            ->assertSee('登録済み写真')
            ->assertSee('まだ写真が登録されていません。');
    }

    public function test_photos_not_directly_attached_to_the_target_car_are_not_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $otherCar = Car::factory()->for($user)->create();
        $otherUsersCar = Car::factory()->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $targetPhoto = Photo::factory()->for($car)->create(['note' => '対象車両の写真']);
        $otherCarPhoto = Photo::factory()->for($otherCar)->create(['note' => '別車両の写真']);
        $otherUsersPhoto = Photo::factory()->for($otherUsersCar)->create(['note' => '他ユーザーの写真']);
        $driveRecordPhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'note' => 'ドライブ記録写真',
        ]);

        $this->actingAs($user)
            ->get(route('cars.show', $car))
            ->assertOk()
            ->assertSee(route('cars.photos.show', [$car, $targetPhoto], absolute: false), escape: false)
            ->assertDontSee(route('cars.photos.show', [$otherCar, $otherCarPhoto], absolute: false), escape: false)
            ->assertDontSee(
                route('cars.photos.show', [$otherUsersCar, $otherUsersPhoto], absolute: false),
                escape: false,
            )
            ->assertDontSee('別車両の写真')
            ->assertDontSee('他ユーザーの写真')
            ->assertDontSee($driveRecordPhoto->note);
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
