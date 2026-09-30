<?php

namespace Tests\Feature\Models;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factory_creates_a_photo_belonging_only_to_a_car(): void
    {
        $photo = Photo::factory()->create();

        $this->assertInstanceOf(Car::class, $photo->car);
        $this->assertNull($photo->drive_record_id);
        $this->assertNull($photo->driveRecord);
    }

    public function test_factory_state_creates_a_photo_belonging_only_to_a_drive_record(): void
    {
        $photo = Photo::factory()->forDriveRecord()->create();

        $this->assertNull($photo->car_id);
        $this->assertNull($photo->car);
        $this->assertInstanceOf(DriveRecord::class, $photo->driveRecord);
    }

    public function test_car_can_create_and_retrieve_photos_through_the_relationship(): void
    {
        $car = Car::factory()->create();

        $photo = $car->photos()->create([
            'file_path' => 'photos/car.jpg',
        ]);

        $this->assertSame($car->id, $photo->car_id);
        $this->assertNull($photo->drive_record_id);
        $this->assertTrue($car->photos->contains($photo));
    }

    public function test_drive_record_can_create_and_retrieve_photos_through_the_relationship(): void
    {
        $driveRecord = DriveRecord::factory()->create();

        $photo = $driveRecord->photos()->create([
            'file_path' => 'photos/drive-record.jpg',
        ]);

        $this->assertNull($photo->car_id);
        $this->assertSame($driveRecord->id, $photo->drive_record_id);
        $this->assertTrue($driveRecord->photos->contains($photo));
    }

    public function test_soft_deleted_car_is_available_only_through_the_explicit_relationship(): void
    {
        $photo = Photo::factory()->create();
        $car = $photo->car;

        $car->delete();
        $photo->unsetRelation('car');

        $this->assertNull($photo->car);
        $this->assertTrue($photo->carWithTrashed->is($car));
    }

    public function test_photo_cannot_be_created_without_an_owner(): void
    {
        $this->expectException(QueryException::class);

        Photo::factory()->create([
            'car_id' => null,
            'drive_record_id' => null,
        ]);
    }

    public function test_photo_cannot_belong_to_both_a_car_and_a_drive_record(): void
    {
        $car = Car::factory()->create();
        $driveRecord = DriveRecord::factory()->create();

        $this->expectException(QueryException::class);

        Photo::factory()->create([
            'car_id' => $car->id,
            'drive_record_id' => $driveRecord->id,
        ]);
    }
}
