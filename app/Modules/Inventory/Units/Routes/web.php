<?php

use App\Modules\Inventory\Units\Presentation\Controllers\UnitController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/units')->name('inventory.units.')->group(function () {
    Route::get('data', [UnitController::class, 'data'])->name('data');
    Route::post('/', [UnitController::class, 'store'])->name('store');
    Route::put('{id}', [UnitController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [UnitController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [UnitController::class, 'restore'])->whereUlid('id')->name('restore');
});
