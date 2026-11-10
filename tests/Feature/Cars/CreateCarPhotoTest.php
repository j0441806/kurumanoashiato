<?php

namespace Tests\Feature\Cars;

use App\Actions\Cars\CreateCarWithPhotos;
use App\Enums\CarCategory;
use App\Livewire\Cars\CreateCar;
use App\Livewire\Cars\EditCar;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class CreateCarPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_and_photos_with_individual_notes_can_be_registered_together(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('interior.png'),
            ])
            ->set('photoNotes.0', 'フロント')
            ->set('photoNotes.1', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cars.index', absolute: false));

        $car = $user->cars()->sole();
        $photos = $car->photos()->orderBy('id')->get();

        $this->assertCount(2, $photos);
        $this->assertSame('フロント', $photos[0]->note);
        $this->assertNull($photos[1]->note);
        $this->assertSame($car->getKey(), $photos[0]->car_id);
        $this->assertSame($car->getKey(), $photos[1]->car_id);
        $this->assertNull($photos[0]->drive_record_id);
        $this->assertNull($photos[1]->drive_record_id);
        Storage::disk('local')->assertExists($photos->pluck('file_path')->all());
    }

    public function test_five_photos_can_be_registered(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $uploads = [];

        for ($index = 0; $index < 5; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', $uploads)
            ->call('save')
            ->assertHasNoErrors();

        $car = $user->cars()->sole();

        $this->assertCount(5, $car->photos);
        $this->assertCount(5, Storage::disk('local')->allFiles("photos/cars/{$car->getKey()}"));
    }

    public function test_more_than_five_photos_are_rejected_without_saving_anything(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', $uploads)
            ->call('save')
            ->assertHasErrors(['photoUploads' => 'max']);

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    #[DataProvider('allowedImageExtensions')]
    public function test_supported_image_format_can_be_registered(string $extension): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image("car.{$extension}")])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('cars', 1);
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_photo_larger_than_five_megabytes_is_rejected_without_saving_a_car(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image('large.jpg')->size(5121)])
            ->call('save')
            ->assertHasErrors(['photoUploads.0']);

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_unsupported_file_is_rejected_without_saving_a_car(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')])
            ->call('save')
            ->assertHasErrors(['photoUploads.0']);

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    #[DataProvider('photoNoteLengths')]
    public function test_photo_note_length_is_validated(int $length, bool $valid): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', str_repeat('あ', $length))
            ->call('save');

        if ($valid) {
            $component->assertHasNoErrors();
            $this->assertDatabaseCount('cars', 1);
            $this->assertDatabaseCount('photos', 1);
        } else {
            $component->assertHasErrors(['photoNotes.0' => 'max']);
            $this->assertDatabaseCount('cars', 0);
            $this->assertDatabaseCount('photos', 0);
        }
    }

    public function test_whitespace_only_photo_note_is_stored_as_null(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', '   ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($user->cars()->sole()->photos()->sole()->note);
    }

    public function test_invalid_car_information_does_not_save_photos_or_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill([
                ...$this->validInput(),
                'maker' => '',
            ])
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->call('save')
            ->assertHasErrors(['maker' => 'required']);

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_selected_file_names_are_shown_with_note_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
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

        $this->actingAs($user);

        $component = Livewire::test(CreateCar::class)
            ->set('photoUploads', [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('rear.jpg'),
            ])
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
        $uploads = [];

        for ($index = 0; $index < 6; $index++) {
            $uploads[] = UploadedFile::fake()->image("photo-{$index}.jpg");
        }

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
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
        $action = Mockery::mock(CreateCarWithPhotos::class);
        $action->shouldReceive('handle')->once()->andThrow(new RuntimeException('Storage failed.'));
        $this->app->instance(CreateCarWithPhotos::class, $action);

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', '保持されるメモ')
            ->call('save')
            ->assertHasErrors(['save'])
            ->assertSee('車両の登録に失敗しました。時間をおいて再度お試しください。')
            ->assertSet('maker', 'Subaru')
            ->assertSet('photoNotes.0', '保持されるメモ')
            ->assertCount('photoUploads', 1);

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_registered_photo_is_shown_on_the_car_edit_screen(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateCar::class)
            ->fill($this->validInput())
            ->set('photoUploads', [UploadedFile::fake()->image('car.jpg')])
            ->set('photoNotes.0', '新規登録時の写真')
            ->call('save')
            ->assertHasNoErrors();

        $car = $user->cars()->sole();
        $photo = $car->photos()->sole();

        Livewire::test(EditCar::class, ['car' => $car])
            ->assertSee('新規登録時の写真')
            ->assertSee(route('cars.photos.show', [$car, $photo], absolute: false), escape: false);
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

    private function validInput(): array
    {
        return [
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'grade' => 'STI Sport R EX',
            'category' => CarCategory::Owned->value,
            'station' => '',
            'memo' => '長距離ドライブ用',
        ];
    }
}
