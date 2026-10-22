<?php

namespace Tests\Feature\DriveRecords;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexDriveRecordsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('drive-records.index'))
            ->assertRedirect(route('login'));
    }

    public function test_only_the_users_drive_records_are_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Subaru',
            'model' => 'Levorg',
        ]);
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'departure' => '東京',
            'destination' => '箱根',
        ]);

        $otherUsersDriveRecord = DriveRecord::factory()->create([
            'departure' => 'Other User Departure',
            'destination' => 'Other User Destination',
        ]);

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertSee($driveRecord->drive_date->format('Y-m-d'))
            ->assertSee('Subaru')
            ->assertSee('Levorg')
            ->assertSee('東京')
            ->assertSee('箱根')
            ->assertSee(route('drive-records.show', $driveRecord, absolute: false), escape: false)
            ->assertDontSee($otherUsersDriveRecord->departure)
            ->assertDontSee($otherUsersDriveRecord->destination);
    }

    public function test_drive_records_are_shown_in_the_defined_order(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-19',
            'created_at' => '2026-10-20 12:00:00',
            'departure' => 'Older Date',
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'created_at' => '2026-10-20 11:00:00',
            'departure' => 'Older Creation',
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'created_at' => '2026-10-20 12:00:00',
            'departure' => 'Lower ID',
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'created_at' => '2026-10-20 12:00:00',
            'departure' => 'Higher ID',
        ]);

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'Higher ID',
                'Lower ID',
                'Older Creation',
                'Older Date',
            ]);
    }

    public function test_road_types_are_shown_as_labels_and_unset_when_empty(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        DriveRecord::factory()->for($car)->create([
            'road_types' => [RoadType::GeneralUrban, RoadType::Expressway],
        ]);
        DriveRecord::factory()->for($car)->create([
            'road_types' => null,
        ]);

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertSee(RoadType::GeneralUrban->label())
            ->assertSee(RoadType::Expressway->label())
            ->assertSee('未設定');
    }

    public function test_distances_are_formatted_without_unnecessary_decimal_zero(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        foreach (['40.0', '40.5', '0.1'] as $distance) {
            DriveRecord::factory()->for($car)->create(['distance' => $distance]);
        }

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertSee('40 km')
            ->assertDontSee('40.0 km')
            ->assertSee('40.5 km')
            ->assertSee('0.1 km');
    }

    public function test_soft_deleted_cars_drive_records_are_not_shown(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'departure' => 'Hidden Departure',
            'destination' => 'Hidden Destination',
        ]);
        $car->delete();

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertDontSee($driveRecord->departure)
            ->assertDontSee($driveRecord->destination)
            ->assertSee('まだドライブ記録が登録されていません。');
    }

    public function test_empty_state_and_registration_link_are_shown(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.index'))
            ->assertOk()
            ->assertSee('まだドライブ記録が登録されていません。')
            ->assertSee('ドライブ記録を登録')
            ->assertSee(route('drive-records.create', absolute: false), escape: false);
    }
}
