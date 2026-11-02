<?php

use App\Modules\Inventory\Items\Presentation\Controllers\ItemClassificationController;
use App\Modules\Inventory\Items\Presentation\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

foreach (['categories', 'brands'] as $kind) {
    Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/'.$kind)->name('inventory.'.$kind.'.')->group(function () use ($kind): void {
        $controller = ItemClassificationController::class;
        Route::get('/', [$controller, 'index'])->defaults('kind', $kind)->name('index');
        Route::post('/', [$controller, 'store'])->defaults('kind', $kind)->name('store');
        Route::put('{id}', [$controller, 'update'])->whereUlid('id')->defaults('kind', $kind)->name('update');
        Route::delete('{id}', [$controller, 'destroy'])->whereUlid('id')->defaults('kind', $kind)->name('destroy');
        Route::post('{id}/restore', [$controller, 'restore'])->whereUlid('id')->defaults('kind', $kind)->name('restore');
    });
}

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/items')->name('inventory.items.')->group(function () {
    Route::get('data', [ItemController::class, 'data'])->name('data');
    Route::get('/', [ItemController::class, 'index'])->name('index');
    Route::get('{id}/image/{mediaId}', [ItemController::class, 'image'])->whereUlid('id')->whereUlid('mediaId')->name('image');
    Route::post('/', [ItemController::class, 'store'])->name('store');
    Route::post('{id}/price-preview', [ItemController::class, 'pricePreview'])->whereUlid('id')->name('price-preview');
    Route::put('{id}', [ItemController::class, 'update'])->whereUlid('id')->name('update');
    Route::delete('{id}', [ItemController::class, 'destroy'])->whereUlid('id')->name('destroy');
    Route::post('{id}/restore', [ItemController::class, 'restore'])->whereUlid('id')->name('restore');
});
