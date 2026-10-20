<?php

use App\Livewire\Cars\CreateCar;
use App\Livewire\Cars\EditCar;
use App\Livewire\Cars\IndexCars;
use App\Livewire\Cars\ShowCar;
use App\Livewire\DriveRecords\CreateDriveRecord;
use App\Livewire\DriveRecords\IndexDriveRecords;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('cars', IndexCars::class)->name('cars.index');
    Route::livewire('cars/create', CreateCar::class)->name('cars.create');
    Route::livewire('cars/{car}/edit', EditCar::class)->name('cars.edit');
    Route::livewire('cars/{car}', ShowCar::class)->name('cars.show');
    Route::livewire('drive-records', IndexDriveRecords::class)->name('drive-records.index');
    Route::livewire('drive-records/create', CreateDriveRecord::class)->name('drive-records.create');
    Route::view('albums', 'albums.index')->name('albums.index');
});

require __DIR__.'/settings.php';
