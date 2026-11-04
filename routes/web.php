<?php

use App\Http\Controllers\CarPhotoController;
use App\Http\Controllers\DriveRecordPhotoController;
use App\Livewire\Cars\CreateCar;
use App\Livewire\Cars\EditCar;
use App\Livewire\Cars\IndexCars;
use App\Livewire\Cars\ShowCar;
use App\Livewire\DriveRecords\CreateDriveRecord;
use App\Livewire\DriveRecords\EditDriveRecord;
use App\Livewire\DriveRecords\IndexDriveRecords;
use App\Livewire\DriveRecords\ShowDriveRecord;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('cars', IndexCars::class)->name('cars.index');
    Route::livewire('cars/create', CreateCar::class)->name('cars.create');
    Route::get('cars/{car}/photos/{photo}', CarPhotoController::class)->name('cars.photos.show');
    Route::livewire('cars/{car}/edit', EditCar::class)->name('cars.edit');
    Route::livewire('cars/{car}', ShowCar::class)->name('cars.show');
    Route::livewire('drive-records', IndexDriveRecords::class)->name('drive-records.index');
    Route::livewire('drive-records/create', CreateDriveRecord::class)->name('drive-records.create');
    Route::get('drive-records/{driveRecord}/photos/{photo}', DriveRecordPhotoController::class)->name('drive-records.photos.show');
    Route::livewire('drive-records/{driveRecord}/edit', EditDriveRecord::class)->name('drive-records.edit');
    Route::livewire('drive-records/{driveRecord}', ShowDriveRecord::class)->name('drive-records.show');
    Route::view('albums', 'albums.index')->name('albums.index');
});

require __DIR__.'/settings.php';
