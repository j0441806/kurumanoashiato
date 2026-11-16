<?php

namespace App\Actions\DriveRecords;

use App\Models\DriveRecord;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateDriveRecordWithPhotos
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, string>  $notes
     */
    public function handle(User $user, int $carId, array $attributes, array $uploads, array $notes): DriveRecord
    {
        $disk = Storage::disk('local');
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($user, $carId, $attributes, $uploads, $notes, $disk, &$storedPaths): DriveRecord {
                $car = $user->cars()->whereKey($carId)->firstOrFail();
                $driveRecord = $car->driveRecords()->create($attributes);

                foreach ($uploads as $index => $upload) {
                    $path = $disk->putFile("photos/drive-records/{$driveRecord->getKey()}", $upload);

                    if ($path === false) {
                        throw new RuntimeException('Failed to store a drive record photo.');
                    }

                    $storedPaths[$index] = $path;
                }

                foreach ($storedPaths as $index => $path) {
                    $note = $notes[$index] ?? null;

                    $driveRecord->photos()->create([
                        'file_path' => $path,
                        'note' => filled($note) ? $note : null,
                    ]);
                }

                return $driveRecord;
            });
        } catch (Throwable $exception) {
            $this->deleteStoredFiles($disk, $storedPaths);

            throw $exception;
        }
    }

    /** @param array<int, string> $paths */
    private function deleteStoredFiles(FilesystemAdapter $disk, array $paths): void
    {
        if ($paths === []) {
            return;
        }

        try {
            if (! $disk->delete($paths)) {
                Log::error('Failed to delete drive record photo files after an unsuccessful drive record creation.', [
                    'paths' => array_values($paths),
                ]);
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);

            Log::error('An exception occurred while deleting drive record photo files after an unsuccessful drive record creation.', [
                'paths' => array_values($paths),
            ]);
        }
    }
}
