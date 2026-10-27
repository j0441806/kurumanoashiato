<?php

namespace Tests\Feature\DriveRecords;

use App\Enums\RoadType;
use App\Livewire\DriveRecords\CreateDriveRecord;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateDriveRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('drive-records.create'))
            ->assertRedirect(route('login'));
    }

    public function test_registration_screen_uses_today_and_shows_only_the_users_cars(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'Subaru',
            'model' => 'Levorg',
        ]);
        Car::factory()->create([
            'maker' => 'Other User Maker',
            'model' => 'Other User Model',
        ]);

        $this->actingAs($user);

        $component = Livewire::test(CreateDriveRecord::class)
            ->assertSet('driveDate', '2026-10-15')
            ->assertSeeHtml('max="2026-10-15"')
            ->assertSee('Subaru')
            ->assertSee('Levorg')
            ->assertDontSee('Other User Maker')
            ->assertDontSee('Other User Model');

        foreach (RoadType::cases() as $roadType) {
            $component->assertSee($roadType->label());
        }

        $component->assertSee((string) $car->id);
    }

    public function test_user_without_cars_sees_guidance_and_registration_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.create'))
            ->assertOk()
            ->assertSee('ドライブ記録を登録するには、先に車両を登録してください。')
            ->assertSee('車両を登録')
            ->assertSee(route('cars.create', absolute: false), escape: false)
            ->assertDontSee('出発地');
    }

    public function test_drive_record_can_be_registered_with_all_fields(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'spots' => '海老名サービスエリア',
                'roadTypes' => [RoadType::GeneralUrban->value, RoadType::Expressway->value],
                'noteTheme' => '温泉を楽しむ',
                'noteGoodPoints' => '景色が良かった',
                'noteIssues' => '出発が遅れた',
                'noteNextAction' => '早めに出発する',
                'noteFree' => 'また訪れたい',
            ])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('drive-records.index', absolute: false));

        $driveRecord = DriveRecord::query()->sole();

        $this->assertSame($car->id, $driveRecord->car_id);
        $this->assertSame('2026-10-15', $driveRecord->drive_date->toDateString());
        $this->assertSame('40.5', $driveRecord->distance);
        $this->assertEquals(
            [RoadType::GeneralUrban, RoadType::Expressway],
            $driveRecord->road_types->all(),
        );
        $this->assertSame('海老名サービスエリア', $driveRecord->spots);
        $this->assertSame('温泉を楽しむ', $driveRecord->note_theme);
        $this->assertSame('景色が良かった', $driveRecord->note_good_points);
        $this->assertSame('出発が遅れた', $driveRecord->note_issues);
        $this->assertSame('早めに出発する', $driveRecord->note_next_action);
        $this->assertSame('また訪れたい', $driveRecord->note_free);
    }

    public function test_optional_fields_are_saved_as_null_when_empty(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->call('save')
            ->assertHasNoErrors();

        $driveRecord = DriveRecord::query()->sole();

        $this->assertNull($driveRecord->spots);
        $this->assertNull($driveRecord->road_types);
        $this->assertNull($driveRecord->note_theme);
        $this->assertNull($driveRecord->note_good_points);
        $this->assertNull($driveRecord->note_issues);
        $this->assertNull($driveRecord->note_next_action);
        $this->assertNull($driveRecord->note_free);
    }

    public function test_required_fields_are_validated_without_saving(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->set('driveDate', '')
            ->call('save')
            ->assertHasErrors([
                'driveDate' => 'required',
                'carId' => 'required',
                'departure' => 'required',
                'destination' => 'required',
                'distance' => 'required',
            ]);

        $this->assertDatabaseCount('drive_records', 0);
    }

    public function test_past_drive_date_can_be_registered(): void
    {
        $this->assertDriveDateCanBeRegistered('2026-10-14');
    }

    public function test_todays_drive_date_can_be_registered(): void
    {
        $this->assertDriveDateCanBeRegistered('2026-10-15');
    }

    public function test_future_drive_date_is_rejected(): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'driveDate' => '2026-10-16',
            ])
            ->call('save')
            ->assertHasErrors(['driveDate' => 'before_or_equal']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    public function test_invalid_date_format_is_rejected(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'driveDate' => '2026/10/15',
            ])
            ->call('save')
            ->assertHasErrors(['driveDate' => 'date_format']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    public function test_another_users_car_cannot_be_used(): void
    {
        $user = User::factory()->create();
        Car::factory()->for($user)->create();
        $otherUsersCar = Car::factory()->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($otherUsersCar))
            ->call('save')
            ->assertHasErrors(['carId' => 'exists']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    public function test_soft_deleted_car_cannot_be_used(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $car->delete();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill($this->validInput($car))
            ->call('save')
            ->assertHasErrors(['carId' => 'exists']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    #[DataProvider('validDistances')]
    public function test_valid_distance_can_be_registered(string $distance): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'distance' => $distance,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('drive_records', 1);
    }

    #[DataProvider('invalidDistances')]
    public function test_invalid_distance_is_rejected(string $distance): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'distance' => $distance,
            ])
            ->call('save')
            ->assertHasErrors(['distance']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    public function test_unknown_road_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'roadTypes' => ['unknown'],
            ])
            ->call('save')
            ->assertHasErrors(['roadTypes.0']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    #[DataProvider('oversizedStrings')]
    public function test_oversized_string_is_rejected(string $field, string $value): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                $field => $value,
            ])
            ->call('save')
            ->assertHasErrors([$field => 'max']);

        $this->assertDatabaseCount('drive_records', 0);
    }

    #[DataProvider('noteFields')]
    public function test_note_with_ten_thousand_characters_can_be_registered(string $field): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                $field => str_repeat('あ', 10000),
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('drive_records', 1);
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

    private function assertDriveDateCanBeRegistered(string $driveDate): void
    {
        $this->travelTo(Carbon::parse('2026-10-15 00:30:00', 'Asia/Tokyo'));

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();

        $this->actingAs($user);

        Livewire::test(CreateDriveRecord::class)
            ->fill([
                ...$this->validInput($car),
                'driveDate' => $driveDate,
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('drive_records', [
            'car_id' => $car->id,
            'drive_date' => $driveDate,
        ]);
    }

    private function validInput(Car $car): array
    {
        return [
            'driveDate' => '2026-10-15',
            'carId' => $car->id,
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
