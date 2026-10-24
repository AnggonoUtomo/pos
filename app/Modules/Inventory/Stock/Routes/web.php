<?php

use App\Modules\Inventory\Stock\Presentation\Controllers\AdjustmentController;
use App\Modules\Inventory\Stock\Presentation\Controllers\StockOpeningController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->prefix('inventory/stock')->name('inventory.stock.')->group(function (): void {
    Route::post('adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    Route::get('/', [StockOpeningController::class, 'index'])->name('index');
    Route::get('data', [StockOpeningController::class, 'data'])->name('data');
    Route::post('openings', [StockOpeningController::class, 'store'])->name('openings.store');
    Route::put('openings/{id}', [StockOpeningController::class, 'update'])->whereUlid('id')->name('openings.update');
    Route::delete('openings/{id}', [StockOpeningController::class, 'destroy'])->whereUlid('id')->name('openings.destroy');
    Route::post('openings/{id}/restore', [StockOpeningController::class, 'restore'])->whereUlid('id')->name('openings.restore');
    Route::post('openings/{id}/post', [StockOpeningController::class, 'post'])->whereUlid('id')->name('openings.post');
});
