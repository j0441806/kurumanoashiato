<?php

namespace Tests\Feature\DriveRecords;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriveRecordPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_a_private_drive_record_photo(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'file_path' => 'photos/drive-records/drive.jpg',
        ]);
        $image = UploadedFile::fake()->image('drive.jpg')->get();
        Storage::disk('local')->put($photo->file_path, $image);

        $response = $this->actingAs($user)
            ->get(route('drive-records.photos.show', [$driveRecord, $photo]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->assertSame($image, $response->streamedContent());
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $photo = Photo::factory()->forDriveRecord()->create();

        $this->get(route('drive-records.photos.show', [$photo->driveRecord, $photo]))
            ->assertRedirect(route('login'));
    }

    public function test_another_user_cannot_view_a_drive_record_photo(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $photo = Photo::factory()->forDriveRecord()->create();
        Storage::disk('local')->put($photo->file_path, 'image');

        $this->actingAs($user)
            ->get(route('drive-records.photos.show', [$photo->driveRecord, $photo]))
            ->assertNotFound();
    }

    public function test_photo_from_another_drive_record_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $otherDriveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $otherDriveRecord->getKey(),
        ]);
        Storage::disk('local')->put($photo->file_path, 'image');

        $this->actingAs($user)
            ->get(route('drive-records.photos.show', [$driveRecord, $photo]))
            ->assertNotFound();
    }

    public function test_photo_for_drive_record_with_soft_deleted_car_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
        ]);
        Storage::disk('local')->put($photo->file_path, 'image');
        $car->delete();

        $this->actingAs($user)
            ->get(route('drive-records.photos.show', [$driveRecord, $photo]))
            ->assertNotFound();
    }

    public function test_missing_photo_file_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
        ]);

        $this->actingAs($user)
            ->get(route('drive-records.photos.show', [$driveRecord, $photo]))
            ->assertNotFound();
    }
}
