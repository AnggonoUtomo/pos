<?php

use App\Modules\Inventory\Items\Presentation\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/items')->name('inventory.items.')->group(function () {
    Route::get('data', [ItemController::class, 'data'])->name('data');
    Route::post('/', [ItemController::class, 'store'])->name('store');
    Route::put('{id}', [ItemController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [ItemController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [ItemController::class, 'restore'])->whereUlid('id')->name('restore');
});
