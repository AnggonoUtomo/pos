<?php

use App\Modules\Inventory\Warehouses\Presentation\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/warehouses')->name('inventory.warehouses.')->group(function () {
    Route::get('data', [WarehouseController::class, 'data'])->name('data');
    Route::post('/', [WarehouseController::class, 'store'])->name('store');
    Route::put('{id}', [WarehouseController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [WarehouseController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [WarehouseController::class, 'restore'])->whereUlid('id')->name('restore');
});
