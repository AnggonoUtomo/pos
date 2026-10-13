<?php

use App\Modules\Sales\POS\Presentation\Controllers\POSController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function () {
    Route::middleware(['web', 'auth'])->group(function (): void {
        Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
        Route::get('/pos/catalog', [POSController::class, 'catalog'])->name('pos.catalog');
        Route::post('/pos/quote', [POSController::class, 'quote'])->name('pos.quote');
    });
});
