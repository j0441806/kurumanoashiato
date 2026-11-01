<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CarPhotoController extends Controller
{
    public function __invoke(Car $car, Photo $photo): StreamedResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $car = $user->cars()->whereKey($car->getKey())->firstOrFail();
        $photo = $car->photos()->whereKey($photo->getKey())->firstOrFail();
        $disk = Storage::disk('local');

        abort_unless($disk->exists($photo->file_path), 404);

        return $disk->response($photo->file_path, headers: [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
