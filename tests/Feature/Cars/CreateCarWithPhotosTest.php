<?php

namespace Tests\Feature\Cars;

use App\Actions\Cars\CreateCarWithPhotos;
use App\Enums\CarCategory;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CreateCarWithPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_car_can_be_created_without_photos(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        $car = app(CreateCarWithPhotos::class)->handle($user, $this->carAttributes(), [], []);

        $this->assertTrue($car->user->is($user));
        $this->assertDatabaseCount('cars', 1);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_false_storage_result_rolls_back_car_and_photos_and_cleans_up_files(): void
    {
        $user = User::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/cars/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andReturnFalse();
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/cars/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(CreateCarWithPhotos::class)->handle($user, $this->carAttributes(), [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ], []);
            $this->fail('A false storage result should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_storage_exception_rolls_back_car_and_photos_and_cleans_up_files(): void
    {
        $user = User::factory()->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/cars/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andThrow(new RuntimeException('Storage failed.'));
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/cars/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(CreateCarWithPhotos::class)->handle($user, $this->carAttributes(), [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ], []);
            $this->fail('A storage exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_database_failure_rolls_back_car_and_photos_and_deletes_stored_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        Photo::creating(fn () => throw new RuntimeException('Database write failed.'));

        try {
            app(CreateCarWithPhotos::class)->handle($user, $this->carAttributes(), [
                UploadedFile::fake()->image('car.jpg'),
            ], ['メモ']);
            $this->fail('A database exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('cars', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    private function carAttributes(): array
    {
        return [
            'maker' => 'Subaru',
            'model' => 'Levorg',
            'grade' => 'STI Sport R EX',
            'category' => CarCategory::Owned,
            'station' => null,
            'memo' => '長距離ドライブ用',
        ];
    }
}
