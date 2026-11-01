<?php

namespace App\Actions\Cars;

use App\Models\Car;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AddCarPhotos
{
    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, string>  $notes
     */
    public function handle(Car $car, array $uploads, array $notes): void
    {
        $disk = Storage::disk('local');
        $storedPaths = [];

        try {
            foreach ($uploads as $index => $upload) {
                $path = $disk->putFile("photos/cars/{$car->getKey()}", $upload);

                if ($path === false) {
                    throw new RuntimeException('Failed to store a car photo.');
                }

                $storedPaths[$index] = $path;
            }

            DB::transaction(function () use ($car, $notes, $storedPaths): void {
                foreach ($storedPaths as $index => $path) {
                    $note = $notes[$index] ?? null;

                    $car->photos()->create([
                        'file_path' => $path,
                        'note' => filled($note) ? $note : null,
                    ]);
                }
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
                Log::error('Failed to delete car photo files after an unsuccessful upload.', [
                    'paths' => array_values($paths),
                ]);
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);

            Log::error('An exception occurred while deleting car photo files after an unsuccessful upload.', [
                'paths' => array_values($paths),
            ]);
        }
    }
}
