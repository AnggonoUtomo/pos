<?php

use App\Modules\Inventory\Items\Presentation\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/items')->name('inventory.items.')->group(function () {
    Route::get('data', [ItemController::class, 'data'])->name('data');
    Route::post('/', [ItemController::class, 'store'])->name('store');
});
