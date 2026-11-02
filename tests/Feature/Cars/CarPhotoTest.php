<?php

namespace Tests\Feature\Cars;

use App\Models\Car;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_a_private_car_photo(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create(['file_path' => 'photos/cars/car.jpg']);
        $image = UploadedFile::fake()->image('car.jpg')->get();
        Storage::disk('local')->put($photo->file_path, $image);

        $response = $this->actingAs($user)
            ->get(route('cars.photos.show', [$car, $photo]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->assertSame($image, $response->streamedContent());
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $photo = Photo::factory()->create();

        $this->get(route('cars.photos.show', [$photo->car, $photo]))
            ->assertRedirect(route('login'));
    }

    public function test_another_user_cannot_view_a_car_photo(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $photo = Photo::factory()->create();
        Storage::disk('local')->put($photo->file_path, 'image');

        $this->actingAs($user)
            ->get(route('cars.photos.show', [$photo->car, $photo]))
            ->assertNotFound();
    }

    public function test_photo_from_another_car_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $otherCar = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($otherCar)->create();
        Storage::disk('local')->put($photo->file_path, 'image');

        $this->actingAs($user)
            ->get(route('cars.photos.show', [$car, $photo]))
            ->assertNotFound();
    }

    public function test_photo_for_soft_deleted_car_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create();
        Storage::disk('local')->put($photo->file_path, 'image');
        $car->delete();

        $this->actingAs($user)
            ->get(route('cars.photos.show', [$car, $photo]))
            ->assertNotFound();
    }

    public function test_missing_photo_file_returns_not_found(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $car = Car::factory()->for($user)->create();
        $photo = Photo::factory()->for($car)->create();

        $this->actingAs($user)
            ->get(route('cars.photos.show', [$car, $photo]))
            ->assertNotFound();
    }
}
