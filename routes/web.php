<?php

use App\Livewire\Cars\CreateCar;
use App\Livewire\Cars\IndexCars;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire('cars', IndexCars::class)->name('cars.index');
    Route::livewire('cars/create', CreateCar::class)->name('cars.create');
    Route::view('drive-records', 'drive-records.index')->name('drive-records.index');
    Route::view('albums', 'albums.index')->name('albums.index');
});

require __DIR__.'/settings.php';
