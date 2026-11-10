<?php

namespace App\Actions\Cars;

use App\Models\Car;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class CreateCarWithPhotos
{
    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, string>  $notes
     */
    public function handle(User $user, array $attributes, array $uploads, array $notes): Car
    {
        $disk = Storage::disk('local');
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($user, $attributes, $uploads, $notes, $disk, &$storedPaths): Car {
                $car = $user->cars()->create($attributes);

                foreach ($uploads as $index => $upload) {
                    $path = $disk->putFile("photos/cars/{$car->getKey()}", $upload);

                    if ($path === false) {
                        throw new RuntimeException('Failed to store a car photo.');
                    }

                    $storedPaths[$index] = $path;
                }

                foreach ($storedPaths as $index => $path) {
                    $note = $notes[$index] ?? null;

                    $car->photos()->create([
                        'file_path' => $path,
                        'note' => filled($note) ? $note : null,
                    ]);
                }

                return $car;
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
                Log::error('Failed to delete car photo files after an unsuccessful car creation.', [
                    'paths' => array_values($paths),
                ]);
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);

            Log::error('An exception occurred while deleting car photo files after an unsuccessful car creation.', [
                'paths' => array_values($paths),
            ]);
        }
    }
}
