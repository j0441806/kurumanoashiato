<?php

namespace Tests\Feature\DriveRecords;

use App\Enums\RoadType;
use App\Livewire\DriveRecords\EditDriveRecord;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EditDriveRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $driveRecord = DriveRecord::factory()->create();

        $this->get(route('drive-records.edit', $driveRecord))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_edit_screen_with_current_values(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Subaru',
            'model' => 'Levorg',
        ]);
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'departure' => '東京',
            'destination' => '箱根',
            'spots' => '海老名サービスエリア',
            'road_types' => [RoadType::GeneralUrban, RoadType::Expressway],
            'distance' => '40.0',
            'note_theme' => '温泉を楽しむ',
            'note_good_points' => '景色が良かった',
            'note_issues' => '出発が遅れた',
            'note_next_action' => '早めに出発する',
            'note_free' => 'また訪れたい',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->assertSet('driveRecordId', $driveRecord->id)
            ->assertSet('driveDate', '2026-10-20')
            ->assertSet('carId', $car->id)
            ->assertSet('departure', '東京')
            ->assertSet('destination', '箱根')
            ->assertSet('spots', '海老名サービスエリア')
            ->assertSet('roadTypes', [RoadType::GeneralUrban->value, RoadType::Expressway->value])
            ->assertSet('distance', '40.0')
            ->assertSet('noteTheme', '温泉を楽しむ')
            ->assertSet('noteGoodPoints', '景色が良かった')
            ->assertSet('noteIssues', '出発が遅れた')
            ->assertSet('noteNextAction', '早めに出発する')
            ->assertSet('noteFree', 'また訪れたい')
            ->assertSeeHtml('max="2026-10-21"')
            ->assertSee('Subaru')
            ->assertSee('Levorg');

        foreach (RoadType::cases() as $roadType) {
            $component->assertSee($roadType->label());
        }
    }

    public function test_optional_null_values_are_initialized_as_empty_form_values(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'spots' => null,
            'road_types' => null,
            'note_theme' => null,
            'note_good_points' => null,
            'note_issues' => null,
            'note_next_action' => null,
            'note_free' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->assertSet('spots', '')
            ->assertSet('roadTypes', [])
            ->assertSet('noteTheme', '')
            ->assertSet('noteGoodPoints', '')
            ->assertSet('noteIssues', '')
            ->assertSet('noteNextAction', '')
            ->assertSet('noteFree', '');
    }

    public function test_only_the_users_active_cars_are_shown(): void
    {
        $user = User::factory()->create();
        $currentCar = Car::factory()->for($user)->create(['maker' => 'Current Car']);
        Car::factory()->for($user)->create(['maker' => 'Selectable Car']);
        $deletedCar = Car::factory()->for($user)->create(['maker' => 'Deleted Car']);
        $deletedCar->delete();
        Car::factory()->create(['maker' => 'Other User Car']);
        $driveRecord = DriveRecord::factory()->for($currentCar)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->assertSee('Current Car')
            ->assertSee('Selectable Car')
            ->assertDontSee('Deleted Car')
            ->assertDontSee('Other User Car');
    }

    public function test_user_cannot_edit_another_users_drive_record(): void
    {
        $user = User::factory()->create();
        $otherUsersDriveRecord = DriveRecord::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.edit', $otherUsersDriveRecord))
            ->assertNotFound();
    }

    public function test_nonexistent_drive_record_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.edit', 999999))
            ->assertNotFound();
    }

    public function test_drive_record_for_soft_deleted_car_returns_not_found(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $car->delete();

        $this->actingAs($user)
            ->get(route('drive-records.edit', $driveRecord))
            ->assertNotFound();
    }

    public function test_drive_record_can_be_updated_and_redirects_to_details(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill($this->validInput($car))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('drive-records.show', $driveRecord, absolute: false));

        $driveRecord->refresh();

        $this->assertSame($car->id, $driveRecord->car_id);
        $this->assertSame('2026-10-15', $driveRecord->drive_date->toDateString());
        $this->assertSame('東京', $driveRecord->departure);
        $this->assertSame('箱根', $driveRecord->destination);
        $this->assertSame('海老名サービスエリア', $driveRecord->spots);
        $this->assertEquals(
            [RoadType::GeneralUrban, RoadType::Expressway],
            $driveRecord->road_types->all(),
        );
        $this->assertSame('40.5', $driveRecord->distance);
        $this->assertSame('温泉を楽しむ', $driveRecord->note_theme);
        $this->assertSame('景色が良かった', $driveRecord->note_good_points);
        $this->assertSame('出発が遅れた', $driveRecord->note_issues);
        $this->assertSame('早めに出発する', $driveRecord->note_next_action);
        $this->assertSame('また訪れたい', $driveRecord->note_free);
    }

    public function test_drive_record_can_be_moved_to_another_owned_car(): void
    {
        $user = User::factory()->create();
        $currentCar = Car::factory()->for($user)->create();
        $newCar = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($currentCar)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill($this->validInput($newCar))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('drive_records', [
            'id' => $driveRecord->id,
            'car_id' => $newCar->id,
        ]);
    }

    #[DataProvider('unavailableCarStates')]
    public function test_unavailable_car_cannot_be_selected(string $state): void
    {
        $user = User::factory()->create();
        $currentCar = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($currentCar)->create();
        $unavailableCar = $state === 'other-user'
            ? Car::factory()->create()
            : Car::factory()->for($user)->create();

        if ($state === 'deleted') {
            $unavailableCar->delete();
        }

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill($this->validInput($unavailableCar))
            ->call('save')
            ->assertHasErrors(['carId' => 'exists']);

        $this->assertDatabaseHas('drive_records', [
            'id' => $driveRecord->id,
            'car_id' => $currentCar->id,
        ]);
    }

    public function test_required_fields_are_validated_without_updating(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'departure' => '変更前の出発地',
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->set('driveDate', '')
            ->set('carId', null)
            ->set('departure', '')
            ->set('destination', '')
            ->set('distance', '')
            ->call('save')
            ->assertHasErrors([
                'driveDate' => 'required',
                'carId' => 'required',
                'departure' => 'required',
                'destination' => 'required',
                'distance' => 'required',
            ]);

        $this->assertDatabaseHas('drive_records', [
            'id' => $driveRecord->id,
            'departure' => '変更前の出発地',
        ]);
    }

    public function test_future_drive_date_is_rejected_without_updating(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create(['drive_date' => '2026-10-14']);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'driveDate' => '2026-10-16',
            ])
            ->call('save')
            ->assertHasErrors(['driveDate' => 'before_or_equal']);

        $this->assertDatabaseHas('drive_records', [
            'id' => $driveRecord->id,
            'drive_date' => '2026-10-14',
        ]);
    }

    #[DataProvider('validDriveDates')]
    public function test_past_and_current_drive_dates_can_be_updated(string $driveDate): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'driveDate' => $driveDate,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($driveDate, $driveRecord->refresh()->drive_date->toDateString());
    }

    public function test_invalid_date_format_is_rejected_without_updating(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create(['drive_date' => '2026-10-14']);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'driveDate' => '2026/10/15',
            ])
            ->call('save')
            ->assertHasErrors(['driveDate' => 'date_format']);

        $this->assertSame('2026-10-14', $driveRecord->refresh()->drive_date->toDateString());
    }

    #[DataProvider('validDistances')]
    public function test_valid_distance_can_be_updated(string $distance): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'distance' => $distance,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(number_format((float) $distance, 1, '.', ''), $driveRecord->refresh()->distance);
    }

    #[DataProvider('invalidDistances')]
    public function test_invalid_distance_is_rejected_without_updating(string $distance): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create(['distance' => '25.5']);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'distance' => $distance,
            ])
            ->call('save')
            ->assertHasErrors(['distance']);

        $this->assertSame('25.5', $driveRecord->refresh()->distance);
    }

    public function test_road_types_can_be_changed_and_cleared(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'road_types' => [RoadType::GeneralUrban],
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'roadTypes' => [RoadType::Winding->value, RoadType::RoughRoad->value],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(
            [RoadType::Winding, RoadType::RoughRoad],
            $driveRecord->refresh()->road_types->all(),
        );

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'roadTypes' => [],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($driveRecord->refresh()->road_types);
    }

    public function test_optional_fields_can_be_cleared_to_null(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'spots' => '変更前',
            'road_types' => [RoadType::Expressway],
            'note_theme' => '変更前',
            'note_good_points' => '変更前',
            'note_issues' => '変更前',
            'note_next_action' => '変更前',
            'note_free' => '変更前',
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'spots' => '',
                'roadTypes' => [],
                'noteTheme' => '',
                'noteGoodPoints' => '',
                'noteIssues' => '',
                'noteNextAction' => '',
                'noteFree' => '',
            ])
            ->call('save')
            ->assertHasNoErrors();

        $driveRecord->refresh();

        $this->assertNull($driveRecord->spots);
        $this->assertNull($driveRecord->road_types);
        $this->assertNull($driveRecord->note_theme);
        $this->assertNull($driveRecord->note_good_points);
        $this->assertNull($driveRecord->note_issues);
        $this->assertNull($driveRecord->note_next_action);
        $this->assertNull($driveRecord->note_free);
    }

    public function test_unknown_and_duplicate_road_types_are_rejected(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'road_types' => [RoadType::GeneralUrban],
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'roadTypes' => ['unknown'],
            ])
            ->call('save')
            ->assertHasErrors(['roadTypes.0']);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                'roadTypes' => [RoadType::Expressway->value, RoadType::Expressway->value],
            ])
            ->call('save')
            ->assertHasErrors(['roadTypes.1' => 'distinct']);

        $this->assertEquals(
            [RoadType::GeneralUrban],
            $driveRecord->refresh()->road_types->all(),
        );
    }

    #[DataProvider('oversizedStrings')]
    public function test_oversized_string_is_rejected_without_updating(string $field, string $value): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'departure' => '変更前の出発地',
        ]);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                $field => $value,
            ])
            ->call('save')
            ->assertHasErrors([$field => 'max']);

        $this->assertSame('変更前の出発地', $driveRecord->refresh()->departure);
    }

    #[DataProvider('noteFields')]
    public function test_note_with_ten_thousand_characters_can_be_updated(string $field): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $note = str_repeat('あ', 10000);

        $this->actingAs($user);

        Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill([
                ...$this->validInput($car),
                $field => $note,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $column = match ($field) {
            'noteTheme' => 'note_theme',
            'noteGoodPoints' => 'note_good_points',
            'noteIssues' => 'note_issues',
            'noteNextAction' => 'note_next_action',
            'noteFree' => 'note_free',
        };

        $this->assertSame($note, $driveRecord->refresh()->getAttribute($column));
    }

    public function test_update_rechecks_access_to_the_original_car(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'departure' => '変更前の出発地',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(EditDriveRecord::class, ['driveRecord' => $driveRecord])
            ->fill($this->validInput($car));

        $car->delete();

        $accessWasDenied = false;

        try {
            $component->call('save');
        } catch (ModelNotFoundException) {
            $accessWasDenied = true;
        }

        $this->assertTrue($accessWasDenied);

        $this->assertDatabaseHas('drive_records', [
            'id' => $driveRecord->id,
            'departure' => '変更前の出発地',
        ]);
    }

    public static function unavailableCarStates(): array
    {
        return [
            'another users car' => ['other-user'],
            'soft deleted car' => ['deleted'],
        ];
    }

    public static function validDriveDates(): array
    {
        return [
            'past date' => ['2026-10-14'],
            'current date' => ['2026-10-15'],
        ];
    }

    public static function validDistances(): array
    {
        return [
            'integer' => ['40'],
            'one decimal zero' => ['40.0'],
            'one decimal' => ['40.5'],
            'minimum' => ['0.1'],
            'maximum' => ['999999.9'],
        ];
    }

    public static function invalidDistances(): array
    {
        return [
            'zero' => ['0'],
            'negative' => ['-1'],
            'two decimals' => ['12.56'],
            'small two decimals' => ['0.01'],
            'integer over maximum' => ['1000000'],
            'decimal over maximum' => ['1000000.0'],
            'not numeric' => ['distance'],
        ];
    }

    public static function oversizedStrings(): array
    {
        return [
            'departure' => ['departure', str_repeat('a', 101)],
            'destination' => ['destination', str_repeat('a', 101)],
            'spots' => ['spots', str_repeat('a', 256)],
            'note theme' => ['noteTheme', str_repeat('あ', 10001)],
            'note good points' => ['noteGoodPoints', str_repeat('あ', 10001)],
            'note issues' => ['noteIssues', str_repeat('あ', 10001)],
            'note next action' => ['noteNextAction', str_repeat('あ', 10001)],
            'note free' => ['noteFree', str_repeat('あ', 10001)],
        ];
    }

    public static function noteFields(): array
    {
        return [
            'note theme' => ['noteTheme'],
            'note good points' => ['noteGoodPoints'],
            'note issues' => ['noteIssues'],
            'note next action' => ['noteNextAction'],
            'note free' => ['noteFree'],
        ];
    }

    private function validInput(Car $car): array
    {
        return [
            'driveDate' => '2026-10-15',
            'carId' => $car->id,
            'departure' => '東京',
            'destination' => '箱根',
            'spots' => '海老名サービスエリア',
            'roadTypes' => [RoadType::GeneralUrban->value, RoadType::Expressway->value],
            'distance' => '40.5',
            'noteTheme' => '温泉を楽しむ',
            'noteGoodPoints' => '景色が良かった',
            'noteIssues' => '出発が遅れた',
            'noteNextAction' => '早めに出発する',
            'noteFree' => 'また訪れたい',
        ];
    }
}
