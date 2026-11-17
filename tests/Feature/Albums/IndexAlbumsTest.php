<?php

namespace Tests\Feature\Albums;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAlbumsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('albums.index'))
            ->assertRedirect(route('login'));
    }

    public function test_users_car_and_drive_record_photos_are_shown_with_labels_and_private_urls(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $carPhoto = Photo::factory()->for($car)->create();
        $driveRecordPhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee('車両写真')
            ->assertSee('ドライブ記録写真')
            ->assertSee(route('albums.show', $carPhoto, absolute: false), escape: false)
            ->assertSee(route('albums.show', $driveRecordPhoto, absolute: false), escape: false)
            ->assertSee(route('cars.photos.show', [$car, $carPhoto], absolute: false), escape: false)
            ->assertSee(
                route('drive-records.photos.show', [$driveRecord, $driveRecordPhoto], absolute: false),
                escape: false,
            );
    }

    public function test_other_users_photos_are_not_shown(): void
    {
        $user = User::factory()->create();
        $usersCar = Car::factory()->for($user)->create();
        $usersPhoto = Photo::factory()->for($usersCar)->create();

        $otherUsersCar = Car::factory()->create();
        $otherUsersDriveRecord = DriveRecord::factory()->for($otherUsersCar)->create();
        $otherUsersCarPhoto = Photo::factory()->for($otherUsersCar)->create();
        $otherUsersDriveRecordPhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $otherUsersDriveRecord->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee(route('cars.photos.show', [$usersCar, $usersPhoto], absolute: false), escape: false)
            ->assertDontSee(
                route('cars.photos.show', [$otherUsersCar, $otherUsersCarPhoto], absolute: false),
                escape: false,
            )
            ->assertDontSee(
                route(
                    'drive-records.photos.show',
                    [$otherUsersDriveRecord, $otherUsersDriveRecordPhoto],
                    absolute: false,
                ),
                escape: false,
            );
    }

    public function test_photos_are_shown_by_created_at_and_then_id_in_descending_order(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $oldest = Photo::factory()->for($car)->create([
            'created_at' => '2026-11-01 10:00:00',
        ]);
        $lowerId = Photo::factory()->for($car)->create([
            'created_at' => '2026-11-02 10:00:00',
        ]);
        $higherId = Photo::factory()->for($car)->create([
            'created_at' => '2026-11-02 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSeeInOrder([
                route('cars.photos.show', [$car, $higherId], absolute: false),
                route('cars.photos.show', [$car, $lowerId], absolute: false),
                route('cars.photos.show', [$car, $oldest], absolute: false),
            ], escape: false);
    }

    public function test_photos_for_soft_deleted_cars_are_not_shown(): void
    {
        $user = User::factory()->create();
        $activeCar = Car::factory()->for($user)->create();
        $activePhoto = Photo::factory()->for($activeCar)->create();

        $deletedCar = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($deletedCar)->create();
        $deletedCarPhoto = Photo::factory()->for($deletedCar)->create();
        $deletedCarsDriveRecordPhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
        ]);
        $deletedCar->delete();

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee(route('cars.photos.show', [$activeCar, $activePhoto], absolute: false), escape: false)
            ->assertDontSee(
                route('cars.photos.show', [$deletedCar, $deletedCarPhoto], absolute: false),
                escape: false,
            )
            ->assertDontSee(
                route(
                    'drive-records.photos.show',
                    [$driveRecord, $deletedCarsDriveRecordPhoto],
                    absolute: false,
                ),
                escape: false,
            );
    }

    public function test_empty_state_is_shown_when_user_has_no_photos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('albums.index'))
            ->assertOk()
            ->assertSee('まだ写真が登録されていません。');
    }
}
