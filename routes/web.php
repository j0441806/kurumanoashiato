<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('cars', 'cars.index')->name('cars.index');
    Route::view('drive-records', 'drive-records.index')->name('drive-records.index');
    Route::view('albums', 'albums.index')->name('albums.index');
});

require __DIR__.'/settings.php';
