<?php

namespace Tests\Feature\Albums;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowAlbumTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $photo = Photo::factory()->create();

        $this->get(route('albums.show', $photo))
            ->assertRedirect(route('login'));
    }

    public function test_users_car_photo_details_are_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create([
            'note' => "フロントから撮影\nお気に入りの一枚",
            'created_at' => '2026-11-17 12:34:56',
        ]);

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertOk()
            ->assertSee("フロントから撮影\nお気に入りの一枚")
            ->assertSee('2026-11-17')
            ->assertSee(route('cars.photos.show', [$car, $photo], absolute: false), escape: false)
            ->assertSee('アルバム一覧へ戻る')
            ->assertSee(route('albums.index', absolute: false), escape: false);
    }

    public function test_users_drive_record_photo_details_are_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'note' => 'ドライブ中に撮影',
            'created_at' => '2026-11-16 09:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertOk()
            ->assertSee('ドライブ中に撮影')
            ->assertSee('2026-11-16')
            ->assertSee(
                route('drive-records.photos.show', [$driveRecord, $photo], absolute: false),
                escape: false,
            );
    }

    public function test_unwritten_note_is_shown_when_note_is_null(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create(['note' => null]);

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertOk()
            ->assertSee('未記入');
    }

    public function test_another_users_car_photo_returns_not_found(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create();

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertNotFound();
    }

    public function test_another_users_drive_record_photo_returns_not_found(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->forDriveRecord()->create();

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertNotFound();
    }

    public function test_missing_photo_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('albums.show', 999999))
            ->assertNotFound();
    }

    public function test_car_photo_for_soft_deleted_car_returns_not_found(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create();
        $car->delete();

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertNotFound();
    }

    public function test_drive_record_photo_for_soft_deleted_car_returns_not_found(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
        ]);
        $car->delete();

        $this->actingAs($user)
            ->get(route('albums.show', $photo))
            ->assertNotFound();
    }
}
