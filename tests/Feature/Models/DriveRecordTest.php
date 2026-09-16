<?php

namespace Tests\Feature\Models;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\DriveRecord;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DriveRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_a_drive_record_with_cast_attributes(): void
    {
        $driveRecord = DriveRecord::factory()->create([
            'drive_date' => '2026-09-16',
            'distance' => 12.5,
            'road_types' => [RoadType::GeneralUrban, RoadType::Expressway],
        ]);

        $this->assertInstanceOf(Car::class, $driveRecord->car);
        $this->assertInstanceOf(CarbonInterface::class, $driveRecord->drive_date);
        $this->assertSame('2026-09-16', $driveRecord->drive_date->toDateString());
        $this->assertSame('12.5', $driveRecord->distance);
        $this->assertInstanceOf(Collection::class, $driveRecord->road_types);
        $this->assertEquals(
            [RoadType::GeneralUrban, RoadType::Expressway],
            $driveRecord->road_types->all(),
        );
    }

    public function test_car_can_create_and_retrieve_drive_records_through_the_relationship(): void
    {
        $car = Car::factory()->create();

        $driveRecord = $car->driveRecords()->create([
            'drive_date' => '2026-09-16',
            'departure' => '東京',
            'destination' => '箱根',
            'distance' => 100.0,
        ]);

        $this->assertSame($car->id, $driveRecord->car_id);
        $this->assertTrue($car->driveRecords->contains($driveRecord));
    }

    public function test_soft_deleted_car_is_available_only_through_the_explicit_relationship(): void
    {
        $driveRecord = DriveRecord::factory()->create();
        $car = $driveRecord->car;

        $car->delete();
        $driveRecord->unsetRelation('car');

        $this->assertNull($driveRecord->car);
        $this->assertTrue($driveRecord->carWithTrashed->is($car));
    }
}
