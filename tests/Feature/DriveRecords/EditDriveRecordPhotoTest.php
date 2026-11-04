<?php

namespace Tests\Feature\DriveRecords;

use App\Actions\DriveRecords\AddDriveRecordPhotos;
use App\Livewire\DriveRecords\EditDriveRecord;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class EditDriveRecordPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_photos_with_individual_notes_can_be_added(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('interior.png'),
            ])
            ->set('photoNotes.0', 'フロント')
            ->set('photoNotes.1', '')
            ->call('addPhotos')
            ->assertHasNoErrors()
            ->assertSet('photoUploads', [])
            ->assertSet('photoNotes', [])
            ->assertDispatched('toast-show');

        $photos = $driveRecord->photos()->orderBy('id')->get();

        $this->assertCount(2, $photos);
        $this->assertSame('フロント', $photos[0]->note);
        $this->assertNull($photos[1]->note);
        $this->assertSame($driveRecord->getKey(), $photos[0]->drive_record_id);
        $this->assertSame($driveRecord->getKey(), $photos[1]->drive_record_id);
        $this->assertNull($photos[0]->car_id);
        $this->assertNull($photos[1]->car_id);
        Storage::disk('local')->assertExists($photos->pluck('file_path')->all());
    }

    public function test_at_least_one_photo_is_required_and_the_error_is_displayed(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads' => 'required'])
            ->assertSee(__('validation.required', ['attribute' => 'photo uploads']));

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_five_photos_can_be_added(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $uploads = [];

        for ($index = 0; $index < 5; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', $uploads)
            ->call('addPhotos')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('photos', 5);
        $this->assertCount(5, Storage::disk('local')->allFiles("photos/drive-records/{$driveRecord->getKey()}"));
    }

    public function test_whitespace_only_photo_note_is_stored_as_null(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', '   ')
            ->call('addPhotos')
            ->assertHasNoErrors();

        $this->assertNull($driveRecord->photos()->sole()->note);
    }

    public function test_photo_save_failure_is_displayed_without_clearing_the_selection(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $action = Mockery::mock(AddDriveRecordPhotos::class);
        $action->shouldReceive('handle')->once()->andThrow(new RuntimeException('Storage failed.'));
        $this->app->instance(AddDriveRecordPhotos::class, $action);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads'])
            ->assertSee('写真の追加に失敗しました。時間をおいて再度お試しください。')
            ->assertCount('photoUploads', 1);
    }

    public function test_selected_file_names_are_shown_with_note_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('rear.webp'),
            ])
            ->assertSee('front.jpg')
            ->assertSee('rear.webp')
            ->assertSee('写真のメモ（任意）');
    }

    public function test_notes_follow_the_same_temporary_file_when_selection_order_changes(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $front = UploadedFile::fake()->image('front.jpg');
        $rear = UploadedFile::fake()->image('rear.jpg');

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [$front, $rear])
            ->set('photoNotes.0', 'フロントのメモ')
            ->set('photoNotes.1', 'リアのメモ');

        $instance = $component->instance();
        $uploads = $instance->photoUploads;
        $instance->photoUploads = [$uploads[1], $uploads[0]];
        $instance->updatedPhotoUploads();

        $this->assertSame(['リアのメモ', 'フロントのメモ'], $instance->photoNotes);
    }

    public function test_reset_clears_uploads_notes_input_key_and_validation_errors(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', $uploads)
            ->set('photoNotes.0', 'リセットされるメモ')
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads' => 'max'])
            ->call('resetPhotoSelection')
            ->assertSet('photoUploads', [])
            ->assertSet('photoNotes', [])
            ->assertSet('photoUploadKeys', [])
            ->assertSet('photoInputKey', 1)
            ->assertHasNoErrors();
    }

    #[DataProvider('allowedImageExtensions')]
    public function test_supported_image_format_can_be_added(string $extension): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image("car.{$extension}")])
            ->call('addPhotos')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('photos', 1);
    }

    public function test_more_than_five_photos_are_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', $uploads)
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads' => 'max']);

        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_photo_larger_than_five_megabytes_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('large.jpg')->size(5121)])
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads.0']);

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_unsupported_file_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')])
            ->call('addPhotos')
            ->assertHasErrors(['photoUploads.0']);

        $this->assertDatabaseCount('photos', 0);
    }

    #[DataProvider('photoNoteLengths')]
    public function test_photo_note_length_is_validated(int $length, bool $valid): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', str_repeat('あ', $length))
            ->call('addPhotos');

        if ($valid) {
            $component->assertHasNoErrors();
            $this->assertDatabaseCount('photos', 1);
        } else {
            $component->assertHasErrors(['photoNotes.0' => 'max']);
            $component->assertSee(__('validation.max.string', [
                'attribute' => 'photoNotes.0',
                'max' => 1000,
            ]));
            $this->assertDatabaseCount('photos', 0);
        }
    }

    public function test_soft_deleted_car_cannot_receive_photos_after_screen_is_opened(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')]);

        $car->delete();

        $this->expectException(ModelNotFoundException::class);

        $component->call('addPhotos');
    }

    public function test_drive_record_moved_to_another_user_cannot_receive_photos_after_screen_is_opened(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')]);

        $driveRecord->car()->associate(Car::factory()->create())->save();

        $this->expectException(ModelNotFoundException::class);

        $component->call('addPhotos');
    }

    public function test_adding_photos_does_not_save_changed_drive_record_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create(['departure' => 'Original Departure']);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('departure', 'Unsaved Departure')
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->call('addPhotos')
            ->assertHasNoErrors();

        $this->assertSame('Original Departure', $driveRecord->refresh()->departure);
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_saving_drive_record_information_does_not_add_selected_photos(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('departure', 'Updated Departure')
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Updated Departure', $driveRecord->refresh()->departure);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_registered_photos_and_notes_are_shown(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $photo = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'file_path' => 'photos/drive-records/car.jpg',
            'note' => 'お気に入りの写真',
        ]);
        Storage::disk('local')->put($photo->file_path, 'image');

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->assertSee('お気に入りの写真')
            ->assertSee(route('drive-records.photos.show', [$driveRecord, $photo], absolute: false), escape: false);
    }

    public static function allowedImageExtensions(): array
    {
        return [
            'jpeg' => ['jpg'],
            'png' => ['png'],
            'webp' => ['webp'],
        ];
    }

    public static function photoNoteLengths(): array
    {
        return [
            'maximum' => [1000, true],
            'over maximum' => [1001, false],
        ];
    }
}
