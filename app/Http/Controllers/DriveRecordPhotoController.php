<?php

namespace App\Http\Controllers;

use App\Models\DriveRecord;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveRecordPhotoController extends Controller
{
    public function __invoke(DriveRecord $driveRecord, Photo $photo): StreamedResponse
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        $driveRecord = DriveRecord::query()
            ->whereKey($driveRecord->getKey())
            ->whereHas('car', fn (Builder $query) => $query->where('user_id', $user->id))
            ->firstOrFail();
        $photo = $driveRecord->photos()->whereKey($photo->getKey())->firstOrFail();
        $disk = Storage::disk('local');

        abort_unless($disk->exists($photo->file_path), 404);

        return $disk->response($photo->file_path, headers: [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
