<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('pos', function () {
        return Inertia::render('sales/pos/index');
    })->name('pos.index');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
