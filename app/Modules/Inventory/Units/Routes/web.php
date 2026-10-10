<?php

use App\Modules\Inventory\Units\Presentation\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/units')->name('inventory.units.')->group(function () {
    Route::get('data', [UnitController::class, 'data'])->name('data');
    Route::post('/', [UnitController::class, 'store'])->name('store');
});
