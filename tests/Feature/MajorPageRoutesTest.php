<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MajorPageRoutesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('majorPageRoutes')]
    public function test_guests_are_redirected_to_login(string $routeName): void
    {
        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }

    #[DataProvider('majorPageRoutes')]
    public function test_authenticated_users_can_access_major_pages(string $routeName): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertOk();
    }

    public static function majorPageRoutes(): array
    {
        return [
            'dashboard' => ['dashboard'],
            'cars' => ['cars.index'],
            'drive records' => ['drive-records.index'],
            'albums' => ['albums.index'],
        ];
    }
}
