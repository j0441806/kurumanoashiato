<?php

namespace Tests\Feature\DriveRecords;

use App\Enums\RoadType;
use App\Models\Car;
use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowDriveRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $driveRecord = DriveRecord::factory()->create();

        $this->get(route('drive-records.show', $driveRecord))
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_view_all_drive_record_details(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create([
            'maker' => 'スバル',
            'model' => 'レヴォーグ',
        ]);
        $driveRecord = DriveRecord::factory()->for($car)->create([
            'drive_date' => '2026-10-20',
            'departure' => '東京',
            'destination' => '箱根',
            'spots' => '海老名サービスエリア',
            'road_types' => [RoadType::GeneralUrban, RoadType::Expressway],
            'distance' => '40.5',
            'note_theme' => '温泉を楽しむ',
            'note_good_points' => '景色が良かった',
            'note_issues' => '出発が遅れた',
            'note_next_action' => '早めに出発する',
            'note_free' => 'また訪れたい',
        ]);

        $this->actingAs($user)
            ->get(route('drive-records.show', $driveRecord))
            ->assertOk()
            ->assertSeeInOrder([
                '日付',
                '2026-10-20',
                '車両',
                'スバル / レヴォーグ',
                '出発地',
                '東京',
                '目的地',
                '箱根',
                '立ち寄りポイント',
                '海老名サービスエリア',
                '道路の種類',
                RoadType::GeneralUrban->label(),
                RoadType::Expressway->label(),
                '距離',
                '40.5 km',
                '今回のテーマ',
                '温泉を楽しむ',
                '良かったこと',
                '景色が良かった',
                '課題',
                '出発が遅れた',
                '次回やること',
                '早めに出発する',
                'メモ',
                'また訪れたい',
            ])
            ->assertSee(route('drive-records.edit', $driveRecord, absolute: false), escape: false)
            ->assertSee(route('drive-records.index', absolute: false), escape: false);
    }

    public function test_optional_fields_show_fallback_text_when_absent(): void
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

        $this->actingAs($user)
            ->get(route('drive-records.show', $driveRecord))
            ->assertOk()
            ->assertSeeInOrder([
                '立ち寄りポイント',
                '未記入',
                '道路の種類',
                '未設定',
                '今回のテーマ',
                '未記入',
                '良かったこと',
                '未記入',
                '課題',
                '未記入',
                '次回やること',
                '未記入',
                'メモ',
                '未記入',
            ]);
    }

    public function test_user_cannot_view_another_users_drive_record(): void
    {
        $user = User::factory()->create();
        $otherUsersDriveRecord = DriveRecord::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.show', $otherUsersDriveRecord))
            ->assertNotFound();
    }

    public function test_nonexistent_drive_record_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('drive-records.show', 999999))
            ->assertNotFound();
    }

    public function test_drive_record_for_soft_deleted_car_returns_not_found(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create();
        $car->delete();

        $this->actingAs($user)
            ->get(route('drive-records.show', $driveRecord))
            ->assertNotFound();
    }

    #[DataProvider('distances')]
    public function test_distance_is_formatted(string $distance, string $expected): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $driveRecord = DriveRecord::factory()->for($car)->create(['distance' => $distance]);

        $response = $this->actingAs($user)
            ->get(route('drive-records.show', $driveRecord))
            ->assertOk()
            ->assertSee($expected);

        if ($distance === '40.0') {
            $response->assertDontSee('40.0 km');
        }
    }

    public static function distances(): array
    {
        return [
            'integer display' => ['40.0', '40 km'],
            'decimal display' => ['40.5', '40.5 km'],
            'minimum display' => ['0.1', '0.1 km'],
        ];
    }
}
