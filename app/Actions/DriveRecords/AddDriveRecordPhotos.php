<?php

namespace App\Actions\DriveRecords;

use App\Models\DriveRecord;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class AddDriveRecordPhotos
{
    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, string>  $notes
     */
    public function handle(DriveRecord $driveRecord, array $uploads, array $notes): void
    {
        $disk = Storage::disk('local');
        $storedPaths = [];

        try {
            foreach ($uploads as $index => $upload) {
                $path = $disk->putFile("photos/drive-records/{$driveRecord->getKey()}", $upload);

                if ($path === false) {
                    throw new RuntimeException('Failed to store a drive record photo.');
                }

                $storedPaths[$index] = $path;
            }

            DB::transaction(function () use ($driveRecord, $notes, $storedPaths): void {
                foreach ($storedPaths as $index => $path) {
                    $note = $notes[$index] ?? null;

                    $driveRecord->photos()->create([
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
                Log::error('Failed to delete drive record photo files after an unsuccessful upload.', [
                    'paths' => array_values($paths),
                ]);
            }
        } catch (Throwable $cleanupException) {
            report($cleanupException);

            Log::error('An exception occurred while deleting drive record photo files after an unsuccessful upload.', [
                'paths' => array_values($paths),
            ]);
        }
    }
}
