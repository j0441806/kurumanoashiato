<?php

namespace Tests\Feature\DriveRecords;

use App\Actions\DriveRecords\CreateDriveRecordWithPhotos;
use App\Models\Car;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CreateDriveRecordWithPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_drive_record_can_be_created_without_photos(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $driveRecord = app(CreateDriveRecordWithPhotos::class)
            ->handle($user, $car->getKey(), $this->driveRecordAttributes(), [], []);

        $this->assertTrue($driveRecord->car->is($car));
        $this->assertDatabaseCount('drive_records', 1);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    public function test_another_users_car_cannot_be_used(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $otherUsersCar = Car::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        try {
            app(CreateDriveRecordWithPhotos::class)->handle(
                $user,
                $otherUsersCar->getKey(),
                $this->driveRecordAttributes(),
                [UploadedFile::fake()->image('drive.jpg')],
                [],
            );
        } finally {
            $this->assertDatabaseCount('drive_records', 0);
            $this->assertDatabaseCount('photos', 0);
            $this->assertSame([], Storage::disk('local')->allFiles('photos'));
        }
    }

    public function test_soft_deleted_car_cannot_be_used(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $car->delete();

        $this->expectException(ModelNotFoundException::class);

        try {
            app(CreateDriveRecordWithPhotos::class)->handle(
                $user,
                $car->getKey(),
                $this->driveRecordAttributes(),
                [UploadedFile::fake()->image('drive.jpg')],
                [],
            );
        } finally {
            $this->assertDatabaseCount('drive_records', 0);
            $this->assertDatabaseCount('photos', 0);
            $this->assertSame([], Storage::disk('local')->allFiles('photos'));
        }
    }

    public function test_false_storage_result_rolls_back_records_and_cleans_up_files(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/drive-records/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andReturnFalse();
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/drive-records/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(CreateDriveRecordWithPhotos::class)->handle(
                $user,
                $car->getKey(),
                $this->driveRecordAttributes(),
                [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
                [],
            );
            $this->fail('A false storage result should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_storage_exception_rolls_back_records_and_cleans_up_files(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn('photos/drive-records/1/first.jpg');
        $disk->shouldReceive('putFile')->once()->andThrow(new RuntimeException('Storage failed.'));
        $disk->shouldReceive('delete')
            ->once()
            ->with([0 => 'photos/drive-records/1/first.jpg'])
            ->andReturnTrue();

        Storage::shouldReceive('disk')->once()->with('local')->andReturn($disk);

        try {
            app(CreateDriveRecordWithPhotos::class)->handle(
                $user,
                $car->getKey(),
                $this->driveRecordAttributes(),
                [
                    UploadedFile::fake()->image('first.jpg'),
                    UploadedFile::fake()->image('second.jpg'),
                ],
                [],
            );
            $this->fail('A storage exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
    }

    public function test_database_failure_rolls_back_records_and_deletes_stored_files(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        Photo::creating(fn () => throw new RuntimeException('Database write failed.'));

        try {
            app(CreateDriveRecordWithPhotos::class)->handle(
                $user,
                $car->getKey(),
                $this->driveRecordAttributes(),
                [UploadedFile::fake()->image('drive.jpg')],
                ['メモ'],
            );
            $this->fail('A database exception should fail the operation.');
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('drive_records', 0);
        $this->assertDatabaseCount('photos', 0);
        $this->assertSame([], Storage::disk('local')->allFiles('photos'));
    }

    private function driveRecordAttributes(): array
    {
        return [
            'drive_date' => '2026-10-15',
            'departure' => '東京',
            'destination' => '箱根',
            'spots' => null,
            'road_types' => null,
            'distance' => '40.5',
            'note_theme' => null,
            'note_good_points' => null,
            'note_issues' => null,
            'note_next_action' => null,
            'note_free' => null,
        ];
    }
}
