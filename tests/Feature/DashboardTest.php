<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_dashboard_has_links_to_each_feature(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('cars.index'), false)
            ->assertSee(route('drive-records.index'), false)
            ->assertSee(route('albums.index'), false);
    }

    public function test_latest_drive_date_determines_the_recent_drive(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create(['maker' => 'スバル', 'model' => 'レヴォーグ']);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-01',
            'departure' => '古い出発地',
            'destination' => '古い目的地',
            'created_at' => '2026-11-20 10:00:00',
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-02',
            'departure' => '最新の出発地',
            'destination' => '最新の目的地',
            'created_at' => '2026-11-10 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('スバル / レヴォーグ')
            ->assertSee('2026-11-02')
            ->assertSee('最新の出発地 / 最新の目的地')
            ->assertDontSee('古い出発地');
    }

    public function test_created_at_breaks_a_tie_between_drive_records_on_the_same_date(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-01',
            'departure' => '登録日時が新しい出発地',
            'created_at' => '2026-11-10 11:00:00',
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-01',
            'departure' => 'IDが大きい出発地',
            'created_at' => '2026-11-10 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('登録日時が新しい出発地')
            ->assertDontSee('IDが大きい出発地');
    }

    public function test_id_breaks_a_tie_between_drive_records_with_the_same_date_and_created_at(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $registeredAt = CarbonImmutable::parse('2026-11-10 10:00:00');
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-01',
            'departure' => 'IDが小さい記録',
            'created_at' => $registeredAt,
        ]);
        DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-11-01',
            'departure' => 'IDが大きい記録',
            'created_at' => $registeredAt,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('IDが大きい記録')
            ->assertDontSee('IDが小さい記録');
    }

    public function test_dashboard_counts_only_the_authenticated_users_active_cars(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Car::factory()->count(2)->for($user)->create();
        Car::factory()->for($user)->create()->delete();
        Car::factory()->for($otherUser)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertSee('2台');
    }

    public function test_dashboard_shows_the_latest_car_and_drive_record_photos(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $registeredAt = CarbonImmutable::parse('2026-11-10 10:00:00');
        Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'created_at' => $registeredAt,
        ]);
        Photo::factory()->for($car)->create(['created_at' => $registeredAt]);
        $latestCarPhoto = Photo::factory()->for($car)->create(['created_at' => $registeredAt]);
        $olderCarPhoto = Photo::factory()->for($car)->create(['created_at' => '2026-11-09 10:00:00']);
        $latestDrivePhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'created_at' => $registeredAt,
        ]);
        $olderDrivePhoto = Photo::factory()->forDriveRecord()->create([
            'drive_record_id' => $driveRecord->getKey(),
            'created_at' => '2026-11-09 10:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('車両写真')
            ->assertSee('ドライブ記録写真')
            ->assertSee('alt="車両写真"', false)
            ->assertSee('alt="ドライブ写真"', false)
            ->assertSee(route('cars.photos.show', [$car, $latestCarPhoto]), false)
            ->assertSee(route('drive-records.photos.show', [$driveRecord, $latestDrivePhoto]), false)
            ->assertDontSee(route('cars.photos.show', [$car, $olderCarPhoto]), false)
            ->assertDontSee(route('drive-records.photos.show', [$driveRecord, $olderDrivePhoto]), false);
    }

    public function test_dashboard_shows_empty_messages_when_the_user_has_no_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('まだドライブ記録がありません。', false)
            ->assertSee('0台')
            ->assertSee('まだ車両写真が登録されていません。')
            ->assertSee('まだドライブ記録写真が登録されていません。');
    }

    public function test_dashboard_excludes_other_users_and_soft_deleted_cars_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCar = Car::factory()->for($otherUser)->create();
        $otherDriveRecord = DriveRecord::factory()->for($otherCar)->create(['departure' => '他ユーザーの出発地']);
        Photo::factory()->for($otherCar)->create();
        Photo::factory()->forDriveRecord()->create(['drive_record_id' => $otherDriveRecord->getKey()]);

        $deletedCar = Car::factory()->for($user)->create();
        $deletedDriveRecord = DriveRecord::factory()->for($deletedCar)->create(['departure' => '削除済み車両の出発地']);
        Photo::factory()->for($deletedCar)->create();
        Photo::factory()->forDriveRecord()->create(['drive_record_id' => $deletedDriveRecord->getKey()]);
        $deletedCar->delete();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('0台')
            ->assertSee('まだドライブ記録がありません。')
            ->assertSee('まだ車両写真が登録されていません。')
            ->assertSee('まだドライブ記録写真が登録されていません。')
            ->assertDontSee('他ユーザーの出発地')
            ->assertDontSee('削除済み車両の出発地');
    }
}
