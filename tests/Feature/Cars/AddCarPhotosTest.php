<?php

namespace Tests\Feature\Cars;

use App\Actions\Cars\AddCarPhotos;
use App\Models\Car;
use App\Models\Photo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AddCarPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_false_storage_result_cleans_up_previously_stored_files_without_creating_records(): void
    {
        $car = Car::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/cars/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andReturnFalse();
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/cars/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(AddCarPhotos::class)->handle($car, [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ], []);
            $this->fail('A false storage result should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_upload_failure_keeps_existing_photo_records(): void
    {
        $car = Car::factory()->create();
        $existingPhoto = Photo::factory()->for($car)->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturnFalse();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(AddCarPhotos::class)->handle($car, [
                UploadedFile::fake()->image('new.jpg'),
            ], []);
            $this->fail('A false storage result should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseHas('photos', ['id' => $existingPhoto->getKey()]);
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_storage_exception_cleans_up_previously_stored_files_without_creating_records(): void
    {
        $car = Car::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/cars/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andThrow(new RuntimeException('Storage failed.'));
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/cars/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(AddCarPhotos::class)->handle($car, [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ], []);
            $this->fail('A storage exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('photos', 0);
    }

    public function test_database_failure_rolls_back_records_and_deletes_stored_files(): void
    {
        Storage::fake('local');

        $car = Car::factory()->create();
        Photo::creating(fn () => throw new RuntimeException('Database write failed.'));

        try {
            app(AddCarPhotos::class)->handle($car, [
                UploadedFile::fake()->image('car.jpg'),
            ], ['メモ']);
            $this->fail('A database exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }
}
