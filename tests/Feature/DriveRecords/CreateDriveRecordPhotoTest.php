<?php

namespace Tests\Feature\DriveRecords;

use App\Actions\DriveRecords\CreateDriveRecordWithPhotos;
use App\Livewire\DriveRecords\CreateDriveRecord;
use App\Livewire\DriveRecords\EditDriveRecord;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CreateDriveRecordPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_drive_record_and_photos_with_individual_notes_can_be_registered_together(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('view.png'),
            ])
            ->set('photoNotes.0', '出発前')
            ->set('photoNotes.1', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('drive-records.index', absolute: false));

        $driveRecord = $car->driveRecords()->sole();
        $photos = $driveRecord->photos()->orderBy('id')->get();

        $this->assertCount(2, $photos);
        $this->assertSame('出発前', $photos[0]->note);
        $this->assertNull($photos[1]->note);
        $this->assertSame($driveRecord->getKey(), $photos[0]->drive_record_id);
        $this->assertSame($driveRecord->getKey(), $photos[1]->drive_record_id);
        $this->assertNull($photos[0]->car_id);
        $this->assertNull($photos[1]->car_id);
        Storage::disk('local')->assertExists($photos->pluck('file_path')->all());
    }

    public function test_five_photos_can_be_registered(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $uploads = [];

        for ($index = 0; $index < 5; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', $uploads)
            ->call('save')
            ->assertHasNoErrors();

        $driveRecord = $car->driveRecords()->sole();

        $this->assertCount(5, $driveRecord->photos);
        $this->assertCount(5, Storage::disk('local')->allFiles("photos/drive-records/{$driveRecord->getKey()}"));
    }

    public function test_more_than_five_photos_are_rejected_without_saving_anything(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', $uploads)
            ->call('save')
            ->assertHasErrors(['photoUploads' => 'max']);

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    #[DataProvider('allowedImageExtensions')]
    public function test_supported_image_format_can_be_registered(string $extension): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->image("drive.{$extension}")])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('drive_records', 1);
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_invalid_photo_or_drive_record_information_does_not_save_anything(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([...$this->validInput($car), 'departure' => ''])
            ->set('photoUploads', [UploadedFile::fake()->image('large.jpg')->size(5121)])
            ->call('save')
            ->assertHasErrors(['departure' => 'required', 'photoUploads.0']);

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_unsupported_file_is_rejected_without_saving_a_drive_record(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasErrors(['photoUploads.0']);

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    #[DataProvider('photoNoteLengths')]
    public function test_photo_note_length_is_validated(int $length, bool $valid): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        $component = Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->image('drive.jpg')])
            ->set('photoNotes.0', str_repeat('あ', $length))
            ->call('save');

        if ($valid) {
            $component->assertHasNoErrors();
            $this->assertDatabaseCount('drive_records', 1);
            $this->assertDatabaseCount('photos', 1);
        } else {
            $component->assertHasErrors(['photoNotes.0' => 'max']);
            $this->assertDatabaseCount('drive_records', 0);
            $this->assertDatabaseCount('photos', 0);
        }
    }

    public function test_whitespace_only_photo_note_is_stored_as_null(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->image('drive.jpg')])
            ->set('photoNotes.0', '   ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($car->driveRecords()->sole()->photos()->sole()->note);
    }

    public function test_selected_file_names_are_shown_and_notes_follow_temporary_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Car::factory()->for($user)->create();

        $this->actingAs($user);

        $component = Livewire::test(CreateDriveRecord::class)
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('rear.webp'),
            ])
            ->assertSee('front.jpg')
            ->assertSee('rear.webp')
            ->assertSee('写真のメモ（任意）')
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
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', $uploads)
            ->set('photoNotes.0', 'リセットされるメモ')
            ->call('save')
            ->assertHasErrors(['photoUploads' => 'max'])
            ->call('resetPhotoSelection')
            ->assertSet('photoUploads', [])
            ->assertSet('photoNotes', [])
            ->assertSet('photoUploadKeys', [])
            ->assertSet('photoInputKey', 1)
            ->assertHasNoErrors();
    }

    public function test_save_failure_is_displayed_without_clearing_input(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $action = Mockery::mock(CreateDriveRecordWithPhotos::class);
        $action->shouldReceive('handle')->once()->andThrow(new RuntimeException('Storage failed.'));
        $this->app->instance(CreateDriveRecordWithPhotos::class, $action);

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->image('drive.jpg')])
            ->set('photoNotes.0', '保持されるメモ')
            ->call('save')
            ->assertHasErrors(['save'])
            ->assertSee('ドライブ記録の登録に失敗しました。時間をおいて再度お試しください。')
            ->assertSet('departure', '東京')
            ->assertSet('photoNotes.0', '保持されるメモ')
            ->assertCount('photoUploads', 1);

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_registered_photo_is_shown_on_the_drive_record_edit_screen(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->set('photoUploads', [UploadedFile::fake()->image('drive.jpg')])
            ->set('photoNotes.0', '新規登録時の写真')
            ->call('save')
            ->assertHasNoErrors();

        $driveRecord = $car->driveRecords()->sole();
        $photo = $driveRecord->photos()->sole();

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->assertSee('新規登録時の写真')
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

    /** @return array<string, mixed> */
    private function validInput(Car $car): array
    {
        return [
            'driveDate' => '2026-10-15',
            'carId' => $car->getKey(),
            'departure' => '東京',
            'destination' => '箱根',
            'spots' => '',
            'roadTypes' => [],
            'distance' => '40.5',
            'noteTheme' => '',
            'noteGoodPoints' => '',
            'noteIssues' => '',
            'noteNextAction' => '',
            'noteFree' => '',
        ];
    }
}
